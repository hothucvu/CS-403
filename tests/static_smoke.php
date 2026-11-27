<?php
// php tests/static_smoke.php : kiểm tra cấu trúc, không kết nối database.
if (PHP_SAPI !== 'cli') exit(1);
$root=dirname(__DIR__);
$routes=['home','login','register','cart','barter','create-product','my-products','profile','checkout','my-orders','order-detail','wallet','admin-products','admin-orders','admin-wallet'];
foreach($routes as $page) {
    $path=$root.'/FE/pages/'.$page.'.php';
    // Chi tiết sản phẩm dùng productdetail.php, xem riêng ở dưới.
    if(!is_file($path)){fwrite(STDERR,"Thiếu trang: $path\n");exit(1);}
}
foreach(['BE/checkout-data.php','BE/admin-orders-data.php','BE/admin-products-data.php','BE/wallet-data.php','BE/barter-finalize.php','BE/api/products.php','BE/api/categories.php','BE/api/product.php','BE/api/barter-matches.php','BE/api/price-suggest.php','BE/api/orders.php','BE/api/order-detail.php','FE/pages/productdetail.php'] as $file){
  if(!is_file($root.'/'.$file)){fwrite(STDERR,"Thiếu file: $file\n");exit(1);}
}
if(str_contains(file_get_contents($root.'/csdl.sql'),'INT INT')){fwrite(STDERR,"Lỗi INT INT trong SQL\n");exit(1);}
echo "OK: route files, controllers, SQL typo\n";
