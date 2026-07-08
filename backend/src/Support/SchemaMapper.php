<?php
declare(strict_types=1);

namespace App\Support;

use BangronDB\Collection;

/**
 * Enhanced SSOT Schema Mapper for BangronDB
 * Dynamic - no static files required
 *
 * Maps enhanced UI schema:
 *  type: string|text|email|password|url|slug|int|integer|float|double|number|bool|boolean|checkbox|array|object|json|enum|date|datetime|relation|tags
 *  + label, required, min, max, regex, unique, enum/options, default, readonly, hidden
 *  + ui: placeholder, icon, badge, color
 *  + relation: {db, collection, field, display}
 *  + filterable, sortable, index, searchable
 *
 * To native BangronDB validation schema.
 */
class SchemaMapper
{
    public const SSOT_CONFIG_KEY = 'ssot';
    public const SSOT_VERSION_KEY = 'ssot_version';

    public static function toBangronValidation(array $ssot): array
    {
        $out = [];
        foreach ($ssot as $field => $def) {
            if (!is_array($def)) continue;
            // skip hidden-only? no, still validate if required
            $type = $def['type'] ?? 'string';
            $nativeType = match($type) {
                'text','email','password','url','slug','tags','date','datetime','time','relation' => 'string',
                'enum' => 'string',
                'int','integer' => 'int',
                'float','double','number','decimal' => 'float',
                'bool','boolean','checkbox','switch' => 'bool',
                'array' => 'array',
                'object','json' => 'object',
                default => in_array($type, ['string','int','float','bool','array','object'], true) ? $type : 'string',
            };
            $rule = ['type' => $nativeType];
            foreach (['required','min','max','regex','unique'] as $k) {
                if (array_key_exists($k, $def)) $rule[$k] = $def[$k];
            }
            // enum handling: options or enum
            if ($type === 'enum') {
                $enum = $def['options'] ?? $def['enum'] ?? null;
                if ($enum) $rule['enum'] = $enum;
            }
            // if enum key exists separately
            if (isset($def['enum']) && is_array($def['enum']) && $type !== 'enum') {
                $rule['enum'] = $def['enum'];
            }
            $out[$field] = $rule;
        }
        return $out;
    }

    public static function extractIndexes(array $ssot): array
    {
        $idx = [];
        foreach ($ssot as $field => $def) {
            if (!is_array($def)) continue;
            if (!empty($def['index']) || !empty($def['sortable']) || !empty($def['filterable']) || !empty($def['unique'])) {
                $idx[] = $field;
            }
        }
        return array_values(array_unique($idx));
    }

    public static function extractSearchable(array $ssot): array
    {
        $s = [];
        foreach ($ssot as $f => $def) {
            if (!empty($def['searchable'])) $s[] = $f;
        }
        return $s;
    }

    public static function extractRelations(array $ssot): array
    {
        $rels = [];
        foreach ($ssot as $field => $def) {
            if (($def['type'] ?? '') === 'relation' && isset($def['relation']) && is_array($def['relation'])) {
                $rels[$field] = $def['relation'] + ['label' => $def['label'] ?? $field];
            }
        }
        return $rels;
    }

    public static function defaults(array $ssot): array
    {
        $d = [];
        foreach ($ssot as $field => $def) {
            if (array_key_exists('default', $def)) $d[$field] = $def['default'];
        }
        return $d;
    }

    public static function uiMeta(array $ssot): array
    {
        $meta = [];
        foreach ($ssot as $field => $def) {
            if (!is_array($def)) continue;
            $meta[$field] = [
                'type'       => $def['type'] ?? 'string',
                'label'      => $def['label'] ?? ucfirst(str_replace('_', ' ', $field)),
                'required'   => $def['required'] ?? false,
                'readonly'   => $def['readonly'] ?? false,
                'hidden'     => $def['hidden'] ?? false,
                'placeholder'=> $def['ui']['placeholder'] ?? $def['placeholder'] ?? null,
                'icon'       => $def['ui']['icon'] ?? null,
                'rows'       => $def['rows'] ?? null,
                'options'    => $def['options'] ?? $def['enum'] ?? null,
                'default'    => $def['default'] ?? null,
                'min'        => $def['min'] ?? null,
                'max'        => $def['max'] ?? null,
                'filterable' => $def['filterable'] ?? false,
                'sortable'   => $def['sortable'] ?? false,
                'searchable' => $def['searchable'] ?? false,
                'index'      => $def['index'] ?? false,
                'badge'      => $def['ui']['badge'] ?? false,
                'color'      => $def['ui']['color'] ?? null,
                'relation'   => $def['relation'] ?? null,
                'multiple'   => $def['multiple'] ?? false,
                'unique'     => $def['unique'] ?? false,
                'regex'      => $def['regex'] ?? null,
            ];
        }
        return $meta;
    }

    /**
     * Apply SSOT to a BangronDB collection: set schema, indexes, searchable, and persist SSOT meta.
     */
    public static function applyAll(Collection $col, array $ssot, ?string $encryptionKey = null, bool $persistSSOT = true): array
    {
        // 1. validation schema
        $validation = self::toBangronValidation($ssot);
        $col->setSchema($validation);

        // 2. indexes
        $indexes = self::extractIndexes($ssot);
        $appliedIndexes = [];
        foreach ($indexes as $f) {
            try { $col->createIndex($f); $appliedIndexes[] = $f; } catch (\Throwable $e) {}
        }

        // 3. searchable
        $searchable = self::extractSearchable($ssot);
        if ($searchable) {
            if ($encryptionKey) {
                try { $col->setEncryptionKey($encryptionKey); } catch (\Throwable $e) {}
            }
            try { $col->setSearchableFields($searchable, true); } catch (\Throwable $e) {}
        }

        // 4. store SSOT meta in custom_config
        if ($persistSSOT && method_exists($col, 'setCustomConfig')) {
            $col->setCustomConfig(self::SSOT_CONFIG_KEY, $ssot);
            $col->setCustomConfig(self::SSOT_VERSION_KEY, date('c'));
            $col->setCustomConfig('ssot_ui', self::uiMeta($ssot));
            $col->setCustomConfig('ssot_relations', self::extractRelations($ssot));
        }

        $col->saveConfiguration();

        return [
            'validation' => $validation,
            'indexes_applied' => $appliedIndexes,
            'searchable' => $searchable,
            'relations' => self::extractRelations($ssot),
            'defaults' => self::defaults($ssot),
        ];
    }

    public static function loadSSOT(Collection $col): ?array
    {
        if (method_exists($col, 'getCustomConfig')) {
            $ssot = $col->getCustomConfig(self::SSOT_CONFIG_KEY, null);
            if (is_array($ssot) && $ssot) return $ssot;
        }
        return null;
    }

    /**
     * Convert native BangronDB schema back to a minimal SSOT (lossy – UI meta missing).
     */
    public static function fromBangronSchema(array $native, array $existingSSOT = []): array
    {
        $ssot = $existingSSOT;
        foreach ($native as $field => $rules) {
            $base = $ssot[$field] ?? [];
            $base['type'] = $base['type'] ?? ($rules['type'] ?? 'string');
            foreach (['required','min','max','regex','unique'] as $k) {
                if (isset($rules[$k])) $base[$k] = $rules[$k];
            }
            if (isset($rules['enum'])) {
                $base['type'] = 'enum';
                $base['options'] = $rules['enum'];
            }
            if (!isset($base['label'])) $base['label'] = ucfirst(str_replace('_',' ', $field));
            $ssot[$field] = $base;
        }
        return $ssot;
    }
}
