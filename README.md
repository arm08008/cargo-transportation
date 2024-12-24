1. Run `composer install`
2. Run `php artisan serve`
2. Use {{base_uri}}/api/cargo endpoint (type GET)
3. Request body
   {
   "transport_type": "sea",
   "cargo": [
   {
   "length": 150,
   "width": 100,
   "height": 140,
   "weight": 300,
   "quantity": 10,
   "stacking":"only_bottom"
   },
   {
   "length": 250,
   "width": 150,
   "height": 100,
   "weight": 500,
   "quantity": 20,
   "stacking":"any_stacking"
   },
   {
   "length": 200,
   "width": 100,
   "height": 100,
   "weight": 70,
   "quantity": 10,
   "stacking":"only_top"
   },
   {
   "length": 500,
   "width": 200,
   "height": 100,
   "weight": 400,
   "quantity": 7,
   "stacking":"only_bottom"
   },
   {
   "length": 400,
   "width": 150,
   "height": 200,
   "weight": 100,
   "quantity": 12,
   "stacking":"only_top"
   },
   {
   "length": 150,
   "width": 150,
   "height": 200,
   "weight": 300,
   "quantity": 5,
   "stacking":"no_stacking"
   }
   ]
   }

Please note that the code can be improved, and there are many cases that still need to be discussed and improved.


This is not the fully comleted but you can add something if you want in case you need to test it for trucks, lets add one new thing inside there but if
