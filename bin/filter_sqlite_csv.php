<?php
namespace GDO\TBS\bin;
/**
 * @var array $argv
 */
$in = $argv[1];
$out = $argv[2];

$filtered = filter(file_get_contents($in));
file_put_contents($out, $filtered);

/**
 * This filters the output of sqlite3 exports.
 * SQLite does "" for "
 */
function filter(string $line) : string
{
	$len = strlen($line);
	$i = 0;
	$par = 0;
	$out = '';
	while ($i < $len)
	{
		$c = $line[$i++];
		switch ($c)
		{
			case '\\':
				# simply skip escaped chars... really correct?! Oo
				break;
			
			case '"':
				if ($par)
				{
					if ($line[$i] === '"')
					{
						$out .= '\\"';
						$i++;
					}
					else
					{
						$out .= $c;
						$par = 0;
					}
				}
				else
				{
					$out .= $c;
					$par = 1;
				}
				break;
				
			default:
				$out .= $c;
				break;
				
		}
	}
	return $out;
}
