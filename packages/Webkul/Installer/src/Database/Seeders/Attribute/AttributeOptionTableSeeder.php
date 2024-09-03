<?php

namespace Webkul\Installer\Database\Seeders\Attribute;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttributeOptionTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        DB::table('attribute_options')->delete();
        DB::table('attribute_option_translations')->delete();

        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        DB::table('attribute_options')->insert([
            [
                'id'           => 1,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.red', [], $defaultLocale),
                'sort_order'   => 1,
                'attribute_id' => 23,
            ], [
                'id'           => 2,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.green', [], $defaultLocale),
                'sort_order'   => 2,
                'attribute_id' => 23,
            ], [
                'id'           => 3,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.yellow', [], $defaultLocale),
                'sort_order'   => 3,
                'attribute_id' => 23,
            ], [
                'id'           => 4,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.black', [], $defaultLocale),
                'sort_order'   => 4,
                'attribute_id' => 23,
            ], [
                'id'           => 5,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.white', [], $defaultLocale),
                'sort_order'   => 5,
                'attribute_id' => 23,
            ], [
                'id'           => 6,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.xxs', [], $defaultLocale),
                'sort_order'   => 1,
                'attribute_id' => 24,
            ], [
                'id'           => 7,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.xxxs', [], $defaultLocale),
                'sort_order'   => 2,
                'attribute_id' => 24,
            ], [
                'id'           => 8,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.3xs', [], $defaultLocale),
                'sort_order'   => 3,
                'attribute_id' => 24,
            ], [
                'id'           => 9,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.4xs', [], $defaultLocale),
                'sort_order'   => 4,
                'attribute_id' => 24,
            ], [
                'id'           => 10,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.5xs', [], $defaultLocale),
                'sort_order'   => 5,
                'attribute_id' => 24,
            ], [
                'id'           => 11,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.xs', [], $defaultLocale),
                'sort_order'   => 6,
                'attribute_id' => 24,
            ], [
                'id'           => 12,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.s', [], $defaultLocale),
                'sort_order'   => 7,
                'attribute_id' => 24,
            ], [
                'id'           => 13,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.m', [], $defaultLocale),
                'sort_order'   => 8,
                'attribute_id' => 24,
            ], [
                'id'           => 14,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.l', [], $defaultLocale),
                'sort_order'   => 9,
                'attribute_id' => 24,
            ], [
                'id'           => 15,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.xl', [], $defaultLocale),
                'sort_order'   => 10,
                'attribute_id' => 24,
            ], [
                'id'           => 16,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.xxl', [], $defaultLocale),
                'sort_order'   => 11,
                'attribute_id' => 24,
            ], [
                'id'           => 17,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.xxxl', [], $defaultLocale),
                'sort_order'   => 12,
                'attribute_id' => 24,
            ], [
                'id'           => 18,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.4xl', [], $defaultLocale),
                'sort_order'   => 13,
                'attribute_id' => 24,
            ], [
                'id'           => 19,
                'admin_name'   => trans('installer::app.seeders.attribute.attribute-options.5xl', [], $defaultLocale),
                'sort_order'   => 14,
                'attribute_id' => 24,
            ]
        ]);

        $locales = config('app.locales') ?? [$defaultLocale];

        foreach ($locales as $locale) {
            DB::table('attribute_option_translations')->insert([
                [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.red', [], $locale),
                    'attribute_option_id' => 1,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.green', [], $locale),
                    'attribute_option_id' => 2,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.yellow', [], $locale),
                    'attribute_option_id' => 3,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.black', [], $locale),
                    'attribute_option_id' => 4,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.white', [], $locale),
                    'attribute_option_id' => 5,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.xxs', [], $locale),
                    'attribute_option_id' => 6,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.xxxs', [], $locale),
                    'attribute_option_id' => 7,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.3xs', [], $locale),
                    'attribute_option_id' => 8,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.4xs', [], $locale),
                    'attribute_option_id' => 9,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.5xs', [], $locale),
                    'attribute_option_id' => 10,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.xs', [], $locale),
                    'attribute_option_id' => 11,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.s', [], $locale),
                    'attribute_option_id' => 12,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.m', [], $locale),
                    'attribute_option_id' => 13,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.l', [], $locale),
                    'attribute_option_id' => 14,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.xl', [], $locale),
                    'attribute_option_id' => 15,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.xxl', [], $locale),
                    'attribute_option_id' => 16,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.xxxl', [], $locale),
                    'attribute_option_id' => 17,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.4xl', [], $locale),
                    'attribute_option_id' => 18,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.5xl', [], $locale),
                    'attribute_option_id' => 19,
                ]
            ]);
        }
    }
}
