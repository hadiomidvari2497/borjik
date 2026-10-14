<?php

namespace App\Support;

/**
 * ساختمان فعال (Tenant جاری) برای هر درخواست.
 * به‌صورت singleton ثبت می‌شود (TenancyServiceProvider) و Middleware آن را پر می‌کند.
 */
final class CurrentBuilding
{
    private ?int $id = null;

    /** @var string[] کلید نقش‌های کاربر در این ساختمان */
    private array $roles = [];

    private bool $bypass = false;

    public function set(int $id, array $roleKeys = []): void
    {
        $this->id = $id;
        $this->roles = $roleKeys;
    }

    public function clear(): void
    {
        $this->id = null;
        $this->roles = [];
        $this->bypass = false;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function roles(): array
    {
        return $this->roles;
    }

    public function hasRole(string ...$keys): bool
    {
        return (bool) array_intersect($keys, $this->roles);
    }

    public function bypassing(): bool
    {
        return $this->bypass;
    }

    /** اجرای کد خارج از محدودیت building_id (فقط Guard ادمین، Seeder و دستورهای کنسول). */
    public function withoutScope(callable $callback): mixed
    {
        $previous = $this->bypass;
        $this->bypass = true;
        try {
            return $callback();
        } finally {
            $this->bypass = $previous;
        }
    }

    /** اجرای کد در context یک ساختمان مشخص (برای Job ها و تست‌ها) و بازگرداندن حالت قبلی. */
    public function actingAs(int $buildingId, callable $callback, array $roleKeys = []): mixed
    {
        [$id, $roles, $bypass] = [$this->id, $this->roles, $this->bypass];
        $this->id = $buildingId;
        $this->roles = $roleKeys;
        $this->bypass = false;
        try {
            return $callback();
        } finally {
            [$this->id, $this->roles, $this->bypass] = [$id, $roles, $bypass];
        }
    }
}
