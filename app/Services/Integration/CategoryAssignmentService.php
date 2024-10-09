<?php

namespace App\Services\Integration;

use Illuminate\Support\Collection;
use Webkul\Sitemap\Models\CategoryTranslation;

class CategoryAssignmentService
{
    public array $midocean_to_xdconnects_category = [
        'Drinkware'                  => 'Eating & Drinking',
        'Home & Living'              => 'Home & Living',
        'Car & Safety'               => 'Technology & Accessories',
        'Bags & Travel'              => 'Bags & Travel',
        'Outdoor'                    => 'Sports & Recreation',
        'Tools & Torches'            => 'Premiums & Tools',
        'Phone & Tablet accessories' => 'Technology & Accessories',
        'Healthy Living & Sport'     => 'Sports & Recreation',
        'Audio'                      => 'Technology & Accessories',
        'Headwear'                   => 'Apparel & Accessories',
        'Writing Instruments'        => 'Office & Writing',
        'Portfolios & Notebooks'     => 'Office & Writing',
        'Umbrella'                   => 'Umbrellas & Rain garments',
        'Textile'                    => 'Apparel & Accessories',
    ];

    public array $midocean_to_xdconnects_subcategory = [
        'Water bottles'               => 'Drinkware',
        'Blankets'                    => 'Home & Living',
        'Kitchen accessories'         => 'Kitchenware',
        'interior & accessories'      => 'Accessories',
        'First aid & Home safety'     => 'First aid',
        'Carry shopping bags'         => 'Bags & Travel',
        'Barbecue'                    => 'Outdoor',
        'Tableware'                   => 'Kitchenware',
        'Bathroom textiles'           => 'Textile',
        'Laptop backpacks'            => 'Backpacks & Business bags',
        'Weekend sport bags'          => 'Sport & Outdoor bags',
        'Travel toiletry bags'        => 'Bags & Travel',
        'Travel Accessories'          => 'Travel accessories',
        'Cooler bags'                 => 'Sport & Outdoor bags',
        'Laptop bags executive'       => 'Backpacks & Business bags',
        'Laptop sleeves'              => 'Backpacks & Business bags',
        'Backpacks'                   => 'Backpacks & Business bags',
        'Thermos flasks'              => 'Drinkware',
        'Coffee mugs & tumblers'      => 'Kitchenware',
        'Picnic'                      => 'Outdoor',
        'Carry recycling bags'        => 'Bags & Travel',
        'Outdoor accessories'         => 'Outdoor',
        'Interior'                    => 'Accessories',
        'Measuring tapes'             => 'Tools & Torches',
        'Rulers & cutters'            => 'Tools & Torches',
        'Tool pens'                   => 'Tools & Torches',
        'Pocket knives'               => 'Tools & Torches',
        'Multitools'                  => 'Tools & Torches',
        'Tool sets'                   => 'Tools & Torches',
        'Tool gifts'                  => 'Tools & Torches',
        'Car accessories'             => 'Car accessories',
        'Torches'                     => 'Tools & Torches',
        'Safety vests'                => 'Others',
        'Table accessories'           => 'Home & Living',
        'Stands'                      => 'Home & Living',
        'Candles & Fragrance sticks'  => 'Anti stress/Candies',
        'Glass'                       => 'Kitchenware',
        'Lunchboxes & Foodflasks'     => 'Kitchenware',
        'Desk accessories'            => 'Office & Writing',
        'Gaming accessoires'          => 'Games',
        'Mobile Gadgets'              => 'Phone accessories',
        'USB chargers & hubs'         => 'USBs',
        'Powerbanks'                  => 'Power banks',
        'Car chargers'                => 'Car accessories',
        'Wireless charger'            => 'Phone accessories',
        'Hubs'                        => 'Phone accessories',
        'Sport Accessoires'           => 'Sport & Health',
        'Sport accessories'           => 'Sport & Health',
        'Solar chargers'              => 'Bags & Travel',
        'Backpacks outdoor/adventure' => 'Bags & Travel',
        'Speakers'                    => 'Audio & Sound',
        'Earbuds'                     => 'Audio & Sound',
        'Headphones'                  => 'Audio & Sound',
        'Laserpointers & -presenters' => 'Office & Writing',
        'Garden'                      => 'Outdoor',
        'Drinkware sets'              => 'Drinkware',
        'Ceramic mugs'                => 'Drinkware',
        'Infuser bottles'             => 'Drinkware',
        'Scarves'                     => 'Textile',
        'Hats'                        => 'Head gear',
        'Beanies'                     => 'Head gear',
        'Summer leisure'              => 'Beach items',
        'Sunglasses'                  => 'Eye wear',
        'Light'                       => 'Decoration',
        'Work light'                  => 'Decoration',
        'Table lamp'                  => 'Decoration',
        'Eco pens'                    => 'Office & Writing',
        'Pen sets'                    => 'Office & Writing',
        'Metal pens'                  => 'Office & Writing',
        'Plastic pens'                => 'Office & Writing',
        'Pencils'                     => 'Office & Writing',
        'Anti-theft backpacks'        => 'Bags & Travel',
        'Backpack trolleys'           => 'Bags & Travel',
        'Crossbody bags'              => 'Bags & Travel',
        'Backpacks executive'         => 'Bags & Travel',
        'Document exhibition bags'    => 'Bags & Travel',
        'Trolley bags'                => 'Bags & Travel',
        'Drawstring bags'             => 'Bags & Travel',
        'Travel garment bags'         => 'Bags & Travel',
        'Weekend medium bags'         => 'Bags & Travel',
        'Notebooks basic'             => 'Notebooks',
        'Portfolios deluxe'           => 'Notebooks',
        'Portfolios'                  => 'Notebooks',
        'Portfolios with zipper'      => 'Notebooks',
        'Notebooks executive'         => 'Notebooks',
        'Travel sets'                 => 'Bags & Travel',
        'Umbrellas for 1 person'      => 'Umbrellas',
        'Foldable umbrellas'          => 'Umbrellas',
        'Umbrellas for 2 persons'     => 'Umbrellas',
        'Wine & Bar'                  => 'Drinkware',
        'Jackets'                     => 'Textile',
        'Bodywarmers'                 => 'Textile',
        'Women t-shirts'              => 'Textile',
        'Kids t-shirts'               => 'Textile',
        'Unisex T-Shirts'             => 'Textile',
        'Unisex Polos'                => 'Textile',
        'Unisex Sweatshirts'          => 'Textile',
        'Unisex Pants'                => 'Textile',
        'Unisex Fleece Jackets'       => 'Textile',
        'Ponchos'                     => 'Textile',
        'Mugs and Tumblers'           => 'Drinkware',
    ];

    public array $midocean_to_stricker_category = [
        'Textile'           => 'Textile',
        'Home'              => 'Technology & Accessories',
        'Bags'              => 'Bags & Travel',
        'Personal & Travel' => 'Bags & Travel',
        'Sports & Outdoor'  => 'Sports & Recreation',
        'Technology'        => 'Technology & Accessories',
        'Write'             => 'Office & Writing',
        'Drinkware'         => 'Eating & Drinking',
        'Keychains'         => 'Premiums & Tools',
        'Shopping'          => 'Bags & Travel',
        'Sun & Rain'        => 'Umbrellas & Rain garments',
        'Bodum'             => 'Eating & Drinking',
        'Office'            => 'Office & Writing',
        'Kids'              => 'Kids & Games',
        'Xmas'              => 'Christmas & Winter',
        'Summer'            => 'Sports & Recreation',
    ];

    public array $midocean_to_stricker_subcategory = [
        'Personal Care - Bags'                                => 'Backpacks & Business bags',
        'Bags - PC/Tablet Folders and Pouches'                => 'Travel accessories',
        'Bags - Shoulder and Waist bags'                      => 'Travel accessories',
        'Accessories - Wallets and Coin Pouch'                => 'Travel accessories',
        'Bags - Non-Woven Shopping Bags'                      => 'Shopping bags',
        'Technology - Headsets and Earphones'                 => 'Audio & Sound',
        'Bags - Backpacks'                                    => 'Backpacks & Business bags',
        'Kitchen - Cutlery and Dishes'                        => 'Lunchware',
        'Writing - Plastic Ball pens'                         => 'Writing',
        'Technology - USB/UDP Pen Drives'                     => 'USBs',
        'Writing - Metal Ball pens'                           => 'Writing',
        'Tools - Lanterns'                                    => 'Tools & Torches',
        'Drinkware - Travel Cups'                             => 'Travel accessories',
        'Writing - Plastic and Metal Ball pens'               => 'Writing',
        'Writing - Cases and Pouches'                         => 'Writing',
        'Writing - Pencils and Mechanical Pencils'            => 'Writing',
        'Keychains - Various'                                 => 'Key rings',
        'Keychains - Multifunction'                           => 'Key rings',
        'Bags - Thermal Bags'                                 => 'Sport & Outdoor bags',
        'Laptop/tablet backpacks'                             => 'Backpacks & Business bags',
        'Bags - PC/Tablet Backpacks'                          => 'Backpacks & Business bags',
        'Bags - PC\/Tablet Backpacks'                         => 'Backpacks & Business bags',
        'Bags - Laptop\/Tablet Backpacks'                     => 'Backpacks & Business bags',
        'Diaries and notepads'                                => 'Notebooks',
        'Ceramic mugs'                                        => 'Lunchware',
        'Mugs'                                                => 'Lunchware',
        'Sports bottles'                                      => 'Drinkware',
        'Kitchen utensils'                                    => 'Lunchware',
        'Pets - Pet Accessories'                              => 'Pets',
        'Technology - Smart Watches'                          => 'Phone accessories',
        'Cards'                                               => 'Games',
        'Security - Reflective Accessories'                   => 'Accessories',
        'Christmas props'                                     => 'Decoration',
        'Blankets'                                            => '',
        'Ball Pens'                                           => 'Writing',
        'Leisure - Travel Accessories'                        => 'Travel accessories',
        'Textile - T-Shirts'                                  => 'Textile Category',
        'Textile - Polos'                                     => 'Textile Category',
        'Textile - Pullovers and Sweatshirts'                 => 'Textile Category',
        'Textile - Shirts'                                    => 'Textile Category',
        'Textiles - Coats and vests'                          => 'Textile Category',
        'Textile - Pants & Shorts'                            => 'Textile Category',
        'Textile - Aprons and Smocks'                         => 'Textile Category',
        'Textile - Fashion Accessories'                       => 'Textile Category',
        'Textile - Hats'                                      => 'Textile Category',
        'Drinkware - Tea / coffee sets'                       => 'Drinkware',
        'Kitchen - Utilities'                                 => 'Kitchenware',
        'Drinkware - Sport bottles'                           => 'Drinkware',
        'Drinkware - Thermal bottles'                         => 'Drinkware',
        'Personal care - Bath'                                => 'Personal care',
        'Leisure - Games and Toys'                            => 'Games',
        'Writing - Ball pens with Lid/Roller'                 => 'Writing',
        'Office - Notepads'                                   => 'Office accessories',
        'Identification - Badges and Pins'                    => 'Corporate & Workwear',
        'Leisure - Picnic and BBQ'                            => 'Outdoor',
        'Technology - Speakers'                               => 'Audio & Sound',
        'Technology - Computer Accessories'                   => 'Office accessories',
        'Drinkware - Other Bottles'                           => 'Drinkware',
        'Writing - ECOlogic Ball pens'                        => 'Writing',
        'Identification - Lanyards'                           => 'Corporate & Workwear',
        'Writing - Writing sets'                              => 'Writing',
        'Writing - Higlighters'                               => 'Writing',
        'Writing - Colouring'                                 => 'Writing',
        'Office - Supplies'                                   => 'Office accessories',
        'Christmas - Ornaments and Decorations'               => 'Decoration',
        'Office - Document Folders'                           => 'Office accessories',
        'Catering - Menus and Bill Holders'                   => 'Others',
        'Bags - Cotton Shopping Bags'                         => 'Shopping bags',
        'Bags - Gift Bags'                                    => 'Shopping bags',
        'Sports - Bags'                                       => 'Sport & Outdoor bags',
        'Bags - Shopping Bags Other Materials'                => 'Shopping bags',
        'Bags - Travel bags and trolleys'                     => 'Shopping bags',
        'Bags - Conference Folders'                           => 'Corporate & Workwear',
        'Bags - Drawstring bags'                              => 'Sport & Outdoor bags',
        'Leisure - Beach Accessories'                         => 'Beach items',
        'Drinkware - Wine Accessories'                        => 'Drinkware',
        'Accessories - Sunglasses'                            => 'Eye wear',
        'Bags - Paper Bags'                                   => 'Shopping bags',
        'Bags - Foldable Shopping Bags'                       => 'Shopping bags',
        'Keychains - With light'                              => 'Key rings',
        'Technology - Mobile Phone Accessories'               => 'Phone accessories',
        'Office - Rulers'                                     => 'Office accessories',
        'Drinkware - Mugs'                                    => 'Drinkware',
        'Drinks - Bar Accessories'                            => 'Barware',
        'Kitchen - Boards'                                    => 'Kitchenware',
        'Kitchen - Hermetic Boxes and Lunchboxes'             => 'Kitchenware',
        'Home - Decoration'                                   => 'Decoration',
        'Tools - Various'                                     => 'Decoration',
        'Tools - Pocket knives'                               => 'Tools & Torches',
        'Drinkware - Corkscrew and Bottle Openers'            => 'Drinkware',
        'Personal Care - Pill Boxes'                          => 'Sport & Health',
        'Personal Care - Various'                             => 'Sport & Health',
        'Drinkware - Foldable Bottles'                        => 'Drinkware',
        'Office - Lamps'                                      => 'Office accessories',
        'Car - Auto Accessories'                              => 'Car accessories',
        'Personal Care - Cosmetic sets'                       => 'Personal care',
        'Personal Care - Lip Protector'                       => 'Personal care',
        'Personal Care - Sanitizers and protective equipment' => 'Personal care',
        'Technology - Digital Watches and Desk Stations'      => 'Office accessories ',
        'Technology - Powerbanks and Chargers'                => 'Power banks',
        'Personal Care - Anti-Stress'                         => 'Anti stress/Candies',
        'Textile - Scarves, Gloves and Hats'                  => 'Textile Category',
        'Textile - Blankets'                                  => 'Textile Category',
        'Umbrellas - Automatic'                               => 'Umbrellas',
        'Textile - Chapeus and Panamas'                       => 'Textile Category',
        'Umbrellas - Manual'                                  => 'Umbrellas',
        'Umbrellas - Retractable'                             => 'Umbrellas',
        'Textile - Raincoats'                                 => 'Textile Category',
        'Sports - Towels'                                     => 'Beach items',
        'Sports - Accessories'                                => 'Sport & Outdoor bags',
    ];

    private ?Collection $categories = null;

    public function getCategories(): Collection
    {
        if ($this->categories === null) {
            // Lazy load categories and cache them for future use
            $this->categories = CategoryTranslation::all()->keyBy('name');
        }

        return $this->categories;
    }
    public function StrickerMapTypeToDefaultCategory($categoryName): int
    {
        $categories = $this->getCategories();
        if (array_key_exists($categoryName, $this->midocean_to_stricker_category) &&
            isset($categories[$this->midocean_to_stricker_category[$categoryName]])) {
            return $categories[$this->midocean_to_stricker_category[$categoryName]]->id;
        }

        return $categories['Uncategorized']->id;
    }

    public function StrickerMapSubTypeToDefaultCategory($categoryName): int
    {
        $categories = $this->getCategories();
        if (array_key_exists($categoryName, $this->midocean_to_stricker_subcategory) &&
            isset($categories[$this->midocean_to_stricker_subcategory[$categoryName]])) {
            return $categories[$this->midocean_to_stricker_subcategory[$categoryName]]->id;
        }

        return $categories['Uncategorized']->id;
    }

    public function XDConnectMapTypeToDefaultCategory($categoryName): int
    {
        $categories = $this->getCategories();
        if (array_key_exists($categoryName, $this->midocean_to_xdconnects_category) &&
            isset($categories[$this->midocean_to_xdconnects_category[$categoryName]])) {
            return $categories[$this->midocean_to_xdconnects_category[$categoryName]]->id;
        }

        return $categories['Uncategorized']->id;
    }

    public function XDConnectMapSubTypeToDefaultCategory($categoryName): int
    {
        $categories = $this->getCategories();
        if (array_key_exists($categoryName, $this->midocean_to_xdconnects_subcategory) &&
            isset($categories[$this->midocean_to_xdconnects_subcategory[$categoryName]])) {
            return $categories[$this->midocean_to_xdconnects_subcategory[$categoryName]]->id;
        }

        return $categories['Uncategorized']->id;
    }

}