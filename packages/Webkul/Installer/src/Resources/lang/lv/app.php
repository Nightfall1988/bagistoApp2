<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Default',
            ],
            'attribute-groups' => [
                'description' => 'Apraksts',
                'general' => 'Vispārīgi',
                'inventories' => 'Krājumi',
                'meta-description' => 'Meta apraksts',
                'price' => 'Cena',
                'settings' => 'Iestatījumi',
                'shipping' => 'Piegāde',
            ],
            'attributes' => [
                'brand' => 'Zīmols',
                'color' => 'Krāsa',
                'cost' => 'Pašizmaksa',
                'description' => 'Apraksts',
                'featured' => 'Ieteiktie',
                'guest-checkout' => 'Pasūtījums bez reģistrācijas',
                'height' => 'Augstums',
                'length' => 'Garums',
                'manage-stock' => 'Pārvaldīt krājumus',
                'meta-description' => 'Meta apraksts',
                'meta-keywords' => 'Meta Atslēgvārdi',
                'meta-title' => 'Meta Virsraksts',
                'name' => 'Vārds',
                'new' => 'Jauns',
                'price' => 'Cena',
                'product-number' => 'Preces numurs',
                'short-description' => 'Īss apraksts',
                'size' => 'Izmērs',
                'sku' => 'SKU',
                'special-price' => 'Akcijas Cena',
                'special-price-from' => 'Akcijas cena no',
                'special-price-to' => 'Akcijas cena līdz',
                'status' => 'Status',
                'tax-category' => 'Nodokļu kategorija',
                'url-key' => 'URL Key',
                'visible-individually' => 'Redzams atsevišķi',
                'weight' => 'Svars',
                'width' => 'Platums',
                'material' => 'Materiāls',
                'dimensions' => 'Izmēri'
            ],
            'attribute-options' => [
                'black' => 'Melns',
                'green' => 'Zaļš',
                'l' => 'L',
                'm' => 'M',
                'red' => 'Sarkans',
                's' => 'S',
                'white' => 'Balts',
                'black' => 'Black',
                'green' => 'Green',
                'l' => 'L',
                'm' => 'M',
                's' => 'S',
                'xl' => 'XL',
                'xxl' => 'XXL',
                'xxxl' => 'XXXL',
                '3xl' => '3XL',
                '4xl' => '4XL',
                '5xl' => '5XL',
                'xxs' => 'XXS',
                'xxxs' => 'XXXS',
                '3xs' => '3XS',
                '4xs' => '4XS',
                '5xs' => '5XS',                
                'yellow' => 'Dzeltens',
                'materials' => [
                    'paper' => 'Papīrs',
                    'glass-soda-lime' => 'Glass Soda Lime',
                    'pp' => 'PP',
                    'pet' => 'PET',
                    'rpet' => 'RPET',
                    'wax' => 'Vasks',
                    'silicone' => 'Silikona',
                    'rpet-recycled-cotton' => 'RPET-Pārstrādāta kokvilna',
                    'acrylic' => 'Akrils',
                    'rubber' => 'Gumija',
                    'recycled-aluminium' => 'Pārstrādāts alumīnijs',
                    'pvc' => 'PVC',
                    'pe' => 'PE',
                    'pu' => 'PU',
                    'mdf' => 'MDF',
                    'abs' => 'ABS',
                    'liquid' => 'Šķidrums',
                    'ps' => 'PS',
                    'metal' => 'Metāls',
                    'pet-pvc' => 'PET-PVC',
                    'recycled-paper' => 'Pārstrādāts papīrs',
                    'pla' => 'PLA',
                    'aluminium' => 'Alumīnijs',
                    'cotton' => 'Kokvilna',
                    'wood' => 'Koka',
                    'stainless-steel' => 'Nerūsējošais tērauds',
                    'glass' => 'Stikls',
                    'pet-pa' => 'PET-PA',
                    'pa' => 'PA',
                    'eva' => 'EVA',
                    'recycled-cotton' => 'Pārstrādāta kokvilna',
                    'recycled-stainless-steel' => 'Pārstrādāts nerūsējošais tērauds',
                    'recycled-pp' => 'Pārstrādāts PP',
                    'recycled-pu' => 'Pārstrādāts PU',
                    'bamboo' => 'Bambuss',
                    'jute' => 'Džuta',
                    'glass-high-borosilicate' => 'Augsta borosilikāta stikls',
                    'recycled-abs' => 'Pārstrādāts ABS',
                    'organic-cotton' => 'Organiskā kokvilna',
                    'tritan' => 'Tritāns',
                    'straw' => 'Salmi',
                    'tpe' => 'TPE',
                ],
            ],
        ],
        'category' => [
            'categories' => [
                'description' => 'Galvenās kategorijas apraksts',
                'name' => 'Galvenā',
            ],
        ],
        'cms' => [
            'pages' => [
                'about-us' => [
                    'content' => 'Par mums Lapas saturs',
                    'title' => 'Par mums',
                ],
                'contact-us' => [
                    'content' => 'Sazinieties ar mums Lapas saturs',
                    'title' => 'Sazinies ar mums',
                ],
                'customer-service' => [
                    'content' => 'Klientu apkalpošanas lapas saturs',
                    'title' => 'Klientu apkalpošana',
                ],
                'payment-policy' => [
                    'content' => 'Maksājumu nosacīumi lapas saturs',
                    'title' => 'Maksājumu nosacījumi',
                ],
                'privacy-policy' => [
                    'content' => 'Privātuma politikas lapas saturs',
                    'title' => 'Privātuma politika',
                ],
                'refund-policy' => [
                    'content' => 'Atmaksas nosacījumi lapas saturs',
                    'title' => 'Atmsaksas nosacījumi',
                ],
                'return-policy' => [
                    'content' => 'Atgriešanas politikas lapas saturs',
                    'title' => 'Atgriešanas politika',
                ],
                'shipping-policy' => [
                    'content' => 'Piegādes nosacījumi lapas saturs',
                    'title' => 'Piegādes nosacījumi',
                ],
                'terms-conditions' => [
                    'content' => 'Noteikumu un nosacījumu lapas saturs',
                    'title' => 'Noteikumi un nosacījumi',
                ],
                'terms-of-use' => [
                    'content' => 'Lietošanas noteikumi Lapas saturs',
                    'title' => 'Lietošanas noteikumi',
                ],
                'whats-new' => [
                    'content' => 'Kas jauns lapas saturs',
                    'title' => 'Kas jauns',
                ],
            ],
        ],
        'core' => [
            'channels' => [
                'name' => 'Noklusējums',
                'meta-title' => 'LogoPrint.lv',
                'meta-keywords' => 'LogoPrint | Prezentreklāma, suvenīri, dāvanas',
                'meta-description' => 'Logoprint risina zīmola atpazīstamības palielināšanu, klientu lojalitātes uzlabošanu un jaunu klientu piesaisti ar personalizētām dāvanām. Mēs palīdzam stiprināt attiecības ar klientiem un partneriem, kā arī veicinām darbinieku motivāciju un apbalvošanu, piedāvājot unikālus un praktiskus prezentmateriālus.
',
            ],
            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Israeli Shekel',
                'CNY' => 'Chinese Yuan',
                'EUR' => 'EURO',
                'GBP' => 'Pound Sterling',
                'INR' => 'Indian Rupee',
                'IRR' => 'Iranian Rial',
                'JPY' => 'Japanese Yen',
                'RUB' => 'Russian Ruble',
                'SAR' => 'Saudi Riyal',
                'TRY' => 'Turkish Lira',
                'UAH' => 'Ukrainian Hryvnia',
                'USD' => 'US Dollar',
            ],
            'locales' => [
                'ar' => 'Arabic',
                'bn' => 'Bengali',
                'de' => 'German',
                'en' => 'English',
                'es' => 'Spanish',
                'fa' => 'Persian',
                'fr' => 'French',
                'he' => 'Hebrew',
                'hi_IN' => 'Hindi',
                'it' => 'Italian',
                'ja' => 'Japanese',
                'nl' => 'Dutch',
                'pl' => 'Polish',
                'pt_BR' => 'Brazilian Portuguese',
                'ru' => 'Russian',
                'sin' => 'Sinhala',
                'tr' => 'Turkish',
                'uk' => 'Ukrainian',
                'zh_CN' => 'Chinese',
            ],
        ],
        'customer' => [
            'customer-groups' => [
                'general' => 'General',
                'guest' => 'Guest',
                'wholesale' => 'Wholesale',
            ],
        ],
        'inventory' => [
            'inventory-sources' => [
                'name' => 'Default',
            ],
        ],
        'shop' => [
            'theme-customizations' => [
                'all-products' => [
                    'name' => 'Visas preces',
                    'options' => [
                        'title' => 'Visas preces',
                    ],
                ],
                'bold-collections' => [
                    'content' => [
                        'btn-title' => 'Skatīt visu',
                        'description' => 'Introducing Our New Bold Collections! Elevate your style with daring designs and vibrant statements. Explore striking patterns and bold colors that redefine your wardrobe. Get ready to embrace the extraordinary!',
                        'title' => 'Tekstilizstrādājumi ar personalizētu dizainu',
                    ],
                    'name' => 'Bold Collections',
                ],
                'categories-collections' => [
                    'name' => 'Categories Collections',
                ],
                'featured-collections' => [
                    'name' => 'ieteiktās Kategorijas',
                    'options' => [
                        'title' => 'Ieteiktās Preces',
                    ],
                ],
                'footer-links' => [
                    'name' => 'Footer Links',
                    'options' => [
                        'about-us' => 'Par mums',
                        'contact-us' => 'Sazinies ar mums',
                        'customer-service' => 'Klientu apkalpošana',
                        'payment-policy' => 'Maksājuma nosacījumi',
                        'privacy-policy' => 'Privātuma politika',
                        'refund-policy' => 'Naudas atgriezšanas nosacījumi',
                        'return-policy' => 'Atgriezšanas nosacījumi',
                        'shipping-policy' => 'Piegādes nosacījumi',
                        'terms-conditions' => 'Noteikumi un nosacījumi',
                        'terms-of-use' => 'Lietošanas noteikumi',
                        'whats-new' => 'Kas jauns',
                    ],
                ],
                'game-container' => [
                    'content' => [
                        'sub-title-1' => 'Our Collections',
                        'sub-title-2' => 'Our Collections',
                        'title' => 'The game with our new additions!',
                    ],
                    'name' => 'Game Container',
                ],
                'image-carousel' => [
                    'name' => 'Image Carousel',
                    'sliders' => [
                        'title' => 'Get Ready For New Collection',
                    ],
                ],
                'new-products' => [
                    'name' => 'Jaunas preces',
                    'options' => [
                        'title' => 'Jaunas preces',
                    ],
                ],
                'offer-information' => [
                    'content' => [
                        'title' => 'Saņemiet ATLAIDI savam pirmajam pasūtījumam IEPĒRCIES TAGAD',
                    ],
                    'name' => 'Piedāvājuma informācija',
                ],
                'services-content' => [
                    'description' => [
                        'emi-available-info' => 'No cost EMI available on all major credit cards',
                        'free-shipping-info' => 'Bezmaksas piegādi visiem pasūtījumiem',
                        'product-replace-info' => 'Easy Product Replacement Available!',
                        'time-support-info' => 'Individuāla pieeja un atbalsts',
                    ],
                    'name' => 'Services Content',
                    'title' => [
                        'emi-available' => 'Emi Available',
                        'free-shipping' => 'Bezmaksas piegāde',
                        'product-replace' => 'Product Replace',
                        'time-support' => 'Atbalsts',
                    ],
                ],
                'top-collections' => [
                    'content' => [
                        'sub-title-1' => 'Our Collections',
                        'sub-title-2' => 'Our Collections',
                        'sub-title-3' => 'Our Collections',
                        'sub-title-4' => 'Our Collections',
                        'sub-title-5' => 'Our Collections',
                        'sub-title-6' => 'Our Collections',
                        'title' => 'The game with our new additions!',
                    ],
                    'name' => 'Top Collections',
                ],
            ],
        ],
        'user' => [
            'roles' => [
                'description' => 'This role users will have all the access',
                'name' => 'Administrator',
            ],
            'users' => [
                'name' => 'Example',
            ],
        ],
        'categories' => [
            'office-writing' => 'Biroja piederumi',
            'office-accessories' => 'Biroja aksesuāri ',
            'bags-travel' => 'Somas un ceļojuma piederumi',
            'backpacks-business-bags' => 'Mugursomas un darba somas',
            'premiums-tools' => 'Premiums & Tools',
            'key-rings' => 'Atslēgu piekariņi',
            'notebooks' => 'Piezīmju grāmatiņas',
            'christmas-winter' => 'Christmas & Winter',
            'textile' => 'Tekstils',
            'decoration' => 'Dekorācijas',
            'drinkware' => 'Dzērienu trauki',
            'gift-bag' => 'Dāvanu maisiņi',
            'kids-games' => 'Bērniem un spēles',
            'stuffed-animals' => 'Mīkstās rotaļlietas',
            'eating-drinking' => 'Ēšanai un dzeršanai',
            'kitchenware' => 'Virtuves piederumi',
            'wellness-care' => 'Veselība un kopšana',
            'home-living' => 'Māja un dzīvesstils',
            'shopping-bags' => 'Iepirkuma somas',
            'others' => 'Citi',
            'catalogues' => 'Katalogi',
            'umbrellas-rain-garments' => 'Lietussargi un lietus apģērbi',
            'rain-gear' => 'Lietus inventārs',
            'painting' => 'Gleznošana',
            'anti-stress-candies' => 'Anti stress/Candies',
            'sports-recreation' => 'Sports un atpūta',
            'beach-items' => 'Pludmales preces',
            'sport-outdoor-bags' => 'Sporta un āra somas',
            'travel-accessories' => 'Ceļojumu piederumi',
            'outdoor' => 'Brīvā dabā',
            'apparel-accessories' => 'Apģērbi un aksesuāri',
            'accessories' => 'Aksesuāri',
            'personal-care' => 'Personīgā aprūpe',
            'barware' => 'Bāra piederumi',
            'tools-torches' => 'Instrumenti un lāpas',
            'writing' => 'Rakstīšana',
            'portfolios' => 'Portfeļi',
            'games' => 'Spēles',
            'car-accessories' => 'Auto piederumi',
            'head-gear' => 'Galvas piederumi',
            'events' => 'Pasākumi',
            'umbrellas' => 'Lietussargi',
            'sport-health' => 'Sports un veselība',
            'first-aid' => 'Pirmā palīdzība',
            'technology-accessories' => 'Tehnoloģijas un piederumi',
            'usbs' => 'USB',
            'wireless-chargers' => 'Bezvadu lādētāji',
            'audio-sound' => 'Audio un skaņa',
            'phone-accessories' => 'Tālruņu piederumi',
            'lunchware' => 'Pusdienu trauki',
            'power-banks' => 'Ārējie lādētāji',
            'ball-pens' => 'Pildspalvas',
        ],
    ],
    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin' => 'Admin',
                'bagisto' => 'Bagisto',
                'confirm-password' => 'Confirm Password',
                'email' => 'Email',
                'email-address' => 'admin@example.com',
                'password' => 'Password',
                'title' => 'Create Administrator',
            ],
            'environment-configuration' => [
                'allowed-currencies' => 'Allowed Currencies',
                'allowed-locales' => 'Allowed Locales',
                'application-name' => 'Application Name',
                'bagisto' => 'Bagisto',
                'chinese-yuan' => 'Chinese Yuan (CNY)',
                'database-connection' => 'Database Connection',
                'database-hostname' => 'Database Hostname',
                'database-name' => 'Database Name',
                'database-password' => 'Database Password',
                'database-port' => 'Database Port',
                'database-prefix' => 'Database Prefix',
                'database-username' => 'Database Username',
                'default-currency' => 'Default Currency',
                'default-locale' => 'Default Locale',
                'default-timezone' => 'Default Timezone',
                'default-url' => 'Default URL',
                'default-url-link' => 'https://localhost',
                'dirham' => 'Dirham (AED)',
                'euro' => 'Euro (EUR)',
                'iranian' => 'Iranian Rial (IRR)',
                'israeli' => 'Israeli Shekel (AFN)',
                'japanese-yen' => 'Japanese Yen (JPY)',
                'mysql' => 'Mysql',
                'pgsql' => 'pgSQL',
                'pound' => 'Pound Sterling (GBP)',
                'rupee' => 'Indian Rupee (INR)',
                'russian-ruble' => 'Russian Ruble (RUB)',
                'saudi' => 'Saudi Riyal (SAR)',
                'select-timezone' => 'Select Timezone',
                'sqlsrv' => 'SQLSRV',
                'title' => 'Store Configuration',
                'turkish-lira' => 'Turkish Lira (TRY)',
                'ukrainian-hryvnia' => 'Ukrainian Hryvnia (UAH)',
                'usd' => 'US Dollar (USD)',
                'warning-message' => 'Beware! The settings for your default system languages as well as the default currency are permanent and cannot be changed ever again.',
            ],
            'installation-processing' => [
                'bagisto' => 'Installation Bagisto',
                'bagisto-info' => 'Creating the database tables, this can take a few moments',
                'title' => 'Installation',
            ],
            'installation-completed' => [
                'admin-panel' => 'Admin Panel',
                'bagisto-forums' => 'Bagisto Forum',
                'customer-panel' => 'Customer Panel',
                'explore-bagisto-extensions' => 'Explore Bagisto Extension',
                'title' => 'Installation Completed',
                'title-info' => 'Bagisto is Successfully installed on your system.',
            ],
            'ready-for-installation' => [
                'create-databsae-table' => 'Create the database table',
                'install' => 'Installation',
                'install-info' => 'Bagisto For Installation',
                'install-info-button' => 'Click the button below to',
                'populate-database-table' => 'Populate the database tables',
                'start-installation' => 'Start Installation',
                'title' => 'Ready for Installation',
            ],
            'start' => [
                'locale' => 'Locale',
                'main' => 'Start',
                'select-locale' => 'Select Locale',
                'title' => 'Your Bagisto install',
                'welcome-title' => 'Welcome to Bagisto 2.0.',
            ],
            'server-requirements' => [
                'calendar' => 'Calendar',
                'ctype' => 'cType',
                'curl' => 'cURL',
                'dom' => 'dom',
                'fileinfo' => 'fileInfo',
                'filter' => 'Filter',
                'gd' => 'GD',
                'hash' => 'Hash',
                'intl' => 'intl',
                'json' => 'JSON',
                'mbstring' => 'mbstring',
                'openssl' => 'openssl',
                'pcre' => 'pcre',
                'pdo' => 'pdo',
                'php' => 'PHP',
                'php-version' => '8.1 or higher',
                'session' => 'session',
                'title' => 'System Requirements',
                'tokenizer' => 'tokenizer',
                'xml' => 'XML',
            ],
            'arabic' => 'Arabic',
            'back' => 'Back',
            'bagisto' => 'Bagisto',
            'bagisto-info' => 'a Community Project by',
            'bagisto-logo' => 'Bagisto Logo',
            'bengali' => 'Bengali',
            'chinese' => 'Chinese',
            'continue' => 'Continue',
            'dutch' => 'Dutch',
            'english' => 'English',
            'french' => 'French',
            'german' => 'German',
            'hebrew' => 'Hebrew',
            'hindi' => 'Hindi',
            'installation-description' => 'Bagisto installation typically involves several steps. Here\'s a general outline of the installation process for Bagisto:',
            'installation-info' => 'We are happy to see you here!',
            'installation-title' => 'Welcome to Installation',
            'italian' => 'Italian',
            'japanese' => 'Japanese',
            'persian' => 'Persian',
            'polish' => 'Polish',
            'portuguese' => 'Brazilian Portuguese',
            'russian' => 'Russian',
            'sinhala' => 'Sinhala',
            'spanish' => 'Spanish',
            'title' => 'Bagisto Installer',
            'turkish' => 'Turkish',
            'ukrainian' => 'Ukrainian',
            'webkul' => 'Webkul',
        ],
    ],
];
