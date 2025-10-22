 <?php

function removerAcentos(string $string): string
{
  $map = array(
    '/[áàãâä]/u' => 'a',
    '/[ÁÀÃÂÄ]/u' => 'A',
    '/[éèêë]/u' => 'e',
    '/[ÉÈÊË]/u' => 'E',
    '/[íìîï]/u' => 'i',
    '/[ÍÌÎÏ]/u' => 'I',
    '/[óòõôö]/u' => 'o',
    '/[ÓÒÕÔÖ]/u' => 'O',
    '/[úùûü]/u' => 'u',
    '/[ÚÙÛÜ]/u' => 'U',
    '/[ç]/u' => 'c',
    '/[Ç]/u' => 'C',
    '/[ñ]/u' => 'n',
    '/[Ñ]/u' => 'N',
  );

    return preg_replace(array_keys($map), array_values($map), $string);

}

$value = "AÀ~´~´´~´´ãAÂ``AÀ";

print_r(  removerAcentos($value) );

 ?>