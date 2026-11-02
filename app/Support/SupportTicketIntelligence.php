<?php
namespace App\Support;
use App\Models\SupportTicket;
use Illuminate\Support\Str;
class SupportTicketIntelligence
{
    private const URGENT_TERMS = ['قطعی کامل','از دسترس خارج','نشت اطلاعات','دسترسی غیرمجاز','حذف اطلاعات','اطلاعات پاک','هک','سرقت اطلاعات','کسر وجه','پول کم شد','تراکنش تکراری','پرداخت دوباره','مشکل امنیتی'];
    private const HIGH_TERMS = ['پرداخت ناموفق','خطای پرداخت','شارژ اشتباه','دسترسی ندارم','نمی توانم وارد','اختلال','خطا','خراب','کار نمی کند','صورتحساب','فاکتور','تمدید'];
    private const LOW_TERMS = ['پیشنهاد','بهتر است','درخواست قابلیت','امکان اضافه','ایده','انتقاد','ظاهر صفحه'];
    private const STOP_WORDS = ['برای','این','اون','آن','من','ما','شما','یک','هم','از','به','در','با','را','که','و','یا','اما','اگر','لطفا','لطفاً','خواهش','ممنون','سلام','هست','است','شود','کرد','کرده','می','نمی','روی','تا','بعد','قبل','داشته'];
    public function analyze(SupportTicket $ticket): array
    {
        $body=$ticket->messages()->where('sender_type','user')->where('is_internal',false)->oldest('id')->value('body');
        $text=$this->normalize($ticket->subject.' '.($body??''));
        [$priority,$reason]=$this->suggestPriority($text);
        $match=$this->findDuplicate($ticket,$text);
        return ['priority_suggestion'=>$priority,'priority_suggestion_reason'=>$reason,'duplicate_of_ticket_id'=>$match['ticket_id']??null,'duplicate_match_score'=>$match['score']??null];
    }
    private function suggestPriority(string $text): array
    {
        foreach(self::URGENT_TERMS as $term) if(Str::contains($text,$this->normalize($term))) return ['urgent','عبارت حساس یا نشانه اختلال بحرانی در متن درخواست شناسایی شد.'];
        foreach(self::HIGH_TERMS as $term) if(Str::contains($text,$this->normalize($term))) return ['high','نشانه خطا، اختلال، پرداخت یا مشکل دسترسی در متن درخواست دیده شد.'];
        foreach(self::LOW_TERMS as $term) if(Str::contains($text,$this->normalize($term))) return ['low','متن درخواست بیشتر ماهیت پیشنهاد یا بازخورد دارد.'];
        return ['normal','نشانه‌ای برای تغییر اولویت پیش‌فرض شناسایی نشد.'];
    }
    private function findDuplicate(SupportTicket $ticket,string $text): ?array
    {
        $tokens=$this->tokens($text); if(count($tokens)<3) return null;
        $candidates=SupportTicket::query()->whereKeyNot($ticket->id)->where('category',$ticket->category)->where('created_at','>=',now()->subDays(120))
            ->where(function($q){$q->whereIn('status',['open','in_progress','waiting_customer'])->orWhere(function($r){$r->whereIn('status',['resolved','closed'])->where('updated_at','>=',now()->subDays(30));});})
            ->with(['messages'=>fn($q)=>$q->where('sender_type','user')->where('is_internal',false)->oldest('id')->limit(1)])
            ->latest('created_at')->limit(100)->get();
        $best=null;
        foreach($candidates as $candidate){
            $other=$this->tokens($this->normalize($candidate->subject.' '.($candidate->messages->first()?->body??'')));
            if(count($other)<3) continue;
            $shared=count(array_intersect($tokens,$other)); if($shared<3) continue;
            $score=(int)round((2*$shared/(count($tokens)+count($other)))*100);
            if($score>=58 && ($best===null || $score>$best['score'])) $best=['ticket_id'=>$candidate->id,'score'=>min(100,$score)];
        }
        return $best;
    }
    private function normalize(string $text): string
    {
        $text=str_replace(['ي','ك','ۀ','ة'],['ی','ک','ه','ه'],$text);
        $text=preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{200c}]/u',' ',$text)??$text;
        $text=mb_strtolower($text,'UTF-8');
        $text=preg_replace('/[^\p{L}\p{N}\s]+/u',' ',$text)??$text;
        return trim(preg_replace('/\s+/u',' ',$text)??$text);
    }
    private function tokens(string $text): array
    {
        $tokens=preg_split('/\s+/u',$text,-1,PREG_SPLIT_NO_EMPTY)?:[];
        $tokens=array_filter($tokens,fn($token)=>mb_strlen($token,'UTF-8')>1&&!in_array($token,self::STOP_WORDS,true));
        return array_values(array_unique($tokens));
    }
}
