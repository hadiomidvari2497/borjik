<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class ArticleHtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 's',
        'ul', 'ol', 'li', 'blockquote', 'a', 'pre', 'code', 'table', 'thead',
        'tbody', 'tr', 'th', 'td', 'div', 'span', 'sub', 'sup',
    ];

    private const DROP_TAGS = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form', 'input', 'button', 'textarea', 'select', 'video', 'audio', 'link', 'meta'];

    public static function sanitize(string $html): string
    {
        if (!class_exists(DOMDocument::class)) {
            // Fail closed if the DOM extension is missing rather than rendering unsanitized markup.
            return nl2br(e(strip_tags($html)));
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<!doctype html><html><head><meta charset="UTF-8"></head><body><div id="article-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('article-root');
        if (!$root) {
            return nl2br(e(strip_tags($html)));
        }

        self::cleanChildren($root);
        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (in_array($tag, self::DROP_TAGS, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }

            self::cleanChildren($node);
            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                $parent->replaceChild(self::unwrap($node), $node);
                continue;
            }

            $allowedAttributes = $tag === 'a' ? ['href', 'title'] : (in_array($tag, ['th', 'td'], true) ? ['colspan', 'rowspan', 'dir', 'style'] : ['dir', 'style']);
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);
                if (!in_array($name, $allowedAttributes, true)) {
                    $node->removeAttributeNode($attribute);
                    continue;
                }
                if ($name === 'style') {\n                    if (!in_array($tag, ['p', 'div', 'h2', 'h3', 'h4', 'blockquote', 'li', 'td', 'th'], true) || !preg_match('~^text-align\\s*:\\s*(left|right|center|justify)\\s*;?$~i', $value, $matches)) {\n                        $node->removeAttribute('style');\n                    } else {\n                        $node->setAttribute('style', 'text-align: '.strtolower($matches[1]).';');\n                    }\n                }\n                if ($name === 'href' && !self::safeHref($value)) {
                    $node->removeAttribute('href');
                }
                if (in_array($name, ['colspan', 'rowspan'], true) && (!ctype_digit($value) || (int) $value < 1 || (int) $value > 20)) {
                    $node->removeAttribute($name);
                }
                if ($name === 'dir' && !in_array($value, ['rtl', 'ltr', 'auto'], true)) {
                    $node->removeAttribute('dir');
                }
            }
        }
    }

    private static function safeHref(string $href): bool
    {
        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, '/') || str_starts_with($href, './') || str_starts_with($href, '../')) {
            return true;
        }

        return (bool) preg_match('~^(https?://|mailto:)~i', $href);
    }

    private static function unwrap(DOMElement $element): DOMNode
    {
        $fragment = $element->ownerDocument->createDocumentFragment();
        while ($element->firstChild) {
            $fragment->appendChild($element->firstChild);
        }
        return $fragment;
    }
}
