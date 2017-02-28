<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// if(!function_exists('url_slug')){
//     function url_slug($url)
//     {
//         // $url2 = $url;
//         // $url = preg_replace('~[^\pL\d]+~u', '-', $url);
//         // $url = iconv('utf-8', 'us-ascii//TRANSLIT', $url);
//         // $url = preg_replace('~[^-\w]+~', '', $url);
//         // $url = trim($url, '-');
//         // $url = preg_replace('~-+~', '-', $url);
//         // $url = strtolower($url);
//         // if (empty($url))
//         // return 'n-a';
//         // return $url;
//         // $data[0] = $url;

//         // $url = $url2;
//         $table = array(
//                 'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
//                 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
//                 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
//                 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
//                 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
//                 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
//                 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
//                 'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', ' ' => '-', "'" => '-'
//         );
//         $stripped = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $url);
//         // return strtolower(strtr($url, $table));
//         $data = strtolower(strtr($url, $table));
//         return $data;
//     }
// }