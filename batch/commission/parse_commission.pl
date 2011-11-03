#!/usr/bin/perl

use URI::Escape;
require "../common/common.pm";

$file = shift;
$url_source = uri_unescape($file);
if ($url_source =~ /(2\d{3})/) {
    $url_year = $1;
}
$url_source =~ s/.*html.*\/http/http/;


open FILE, $file;
@lignes = <FILE>;
close FILE;
$content = "@lignes";
$content =~ s/\n//g;
$content =~ s/(<td[^>]*>)(\s*<\/?(a|strong|p|em)[^>]*>)+/$1/gi;
$content =~ s/<\/?(a|strong|p|em)[^>]*>\s*<\/td>/<\/td>/gi;
$content =~ s/<br\/?\s?>/ /ig;
$content =~ s/[ \t]+/ /g;
$content =~ s/&(#160|nbsp);/ /ig;
$content =~ s/\s+/ /g;
$content =~ s/<\/(p|h[1234]|ul|div)>/<\/$1>\n/gi;
$content =~ s/(<h\d[^>]*>)\s*<b>/$1/gi;
$content =~ s/<\/b>\s*(<\/h\d[^>]*>)/$1/gi;

%fonctions = ();

$timestamp = 0;
$nb_seance = 1;
sub print_inter {
	if ($intervention && $intervention ne '<p></p>') {
		if ($intervention =~ /(projet de loi|texte)( n[^<]+)/) {
			$doc = $2;
			$doc =~ s/&[^;]+;//g;
			$numeros_loi = '';
			while ($doc =~ / n\s*(\d+) ?(\(\d+\-\d+\))/g) {
				$numeros_loi .= law_numberize($1,$2).",";
			}
			if ($numeros_loi) {
			    chop($numeros_loi);
			    $numeros_loi =~ s/[^0-9\-\,]//g;
			}
		}
		if ($intervention =~ /amendement( n[^<]+)/) {
			$doc = $1;
			$doc =~ s/&[^;]+;//g;
                        if ($doc =~ / n\s*([COM\-\d]+)/) {
				$amendements = $1;
			}
		}
		$timestamp += 20;
		if ($date !~ /\d{4}\-\d{2}-\d{2}/) {
		    print STDERR "ERROR pas de date pour $file\n";
		    exit 1;
		}
		if (!$commission || $commission =~ /[\/<>]/) {
		    print STDERR "ERROR pas de commission pour $file\n";
		    exit 1;
		}
		print '{"commission": "'.quotize($commission).'", "contexte": "'.$context.'", "intervention": "'.quotize($intervention).'", "timestamp": "'.$timestamp.'", "date": "'.$date.'", "source": "'.$url_source.$source.'", "heure":"'.$heure.'", "intervenant": "'.name_lowerize($intervenant).'", "fonction": "'.$fonction.'", "intervenant_url": "'.$url_intervenant.'", "session":"'.$session.'"';
        	print ', "numeros_loi":"'.$numeros_loi.'"' if ($numeros_loi);
	        print ', "amendements":"'.$amendements.'"' if ($amendements);
		print "}\n";
	}
	$intervenant = '';
	$fonction = '';
	$url_intervenant = '';
	$intervention = '';
	$amendements = '';
}

sub setfonction {
	my $f = shift;
	if ($f =~ /audition de (M[^<]+)/) {
		$a = $1;
		while ($a =~ /(M[me\.]* [^\,\.]+), ([^\,\.]+)/g) {
			$fonctions{$1} = $2;
		}
	}
}

$begin = 0;
$recointer = "(M\\\.?m?e?|Amiral|Général|S\\\.E|Son |colonel)";

$interstrong = 1 if ($content =~ /<(a|strong)[^>]*>\s*($recointer[^<]*)<\/(a|strong)>/i);
foreach (split /\n/, $content) {
	last if (/END : primary/);
	s/ n<sup>[0os\s]+<\/sup>\s*/ n° /ig;
	$begin = 1 if (/name="toc1"/);
#print STDERR "title: $1\n" if (/<title>([^<]*)</);
	if (/TITLE>[^<]*(Commission[^:<]*)/i) {
	    $commission = $1;
	    $commission =~ s/[\s\-]+S[é&eacut;]+nat\s*//i;
	}else {
	    $commission = $1 if (/TITLE>[^<]*((Mission|Office|Délégation|Groupe de travail)[^:<]*)/i);
	    $commission =~ s/[\s\-]+S[é&eacut;]+nat\s*//i;
	}
#	print ;	print "\n";
	if ((!/\d{4}\-\d{4}/) && (/<(h[123])[^>]*>(\s*<[^>]*>)*([^<\(]+\d{4})(\W*<[^>]*>)*\W*<\/(h[123])>/i)) {
#print STDERR "date: $3 $url_year\n";
		@date = datize($3, $url_year);
		if (@date) {
#print STDERR "date:"."@date"." ($timestamp $intervention)\n";
		    print_inter() if ($date && ($intervention !~ /commission.*mixte.*paritaire/i)); # || $intervention =~ /(adopt|rejet)/);
		    $olddate = $date;
                    $date = join '-', @date;
#print STDERR "date:".$date."\n";
		    $heure = '';
		    $session = sessionize(@date);
		    $numeros_loi = '';
		    $nb_seance = 1;
		    print_inter() if ($intervention && !$timestamp);
		    $timestamp = '0' if ($olddate ne $date);
		    next;
		}
	}
	if (/<h[1234][^>]*>(\s*<[^>]*>)*([^<]+)<\/h[1234]>/) {
		$titre = $2;
		next if ($titre =~ /^((com)?mission|comptes rendus |office|délégation|groupe de travail)/i && $titre !~/commission mixte paritaire/i);
		print_inter() if($timestamp);
		$context = $titre;
		setfonction($titre);
		$context =~ s/ -{1,2} / > /;
		$titre =~ s/[\s\(]+suite[\s\)]*$//i if ($context =~ s/[\s\(]+suite[\s\)]*$//i);
		$intervention = '<p>'.$titre.'</p>';
		%fonctions = ();
		$numeros_loi = '';
		$is_newcontext = 1;
	}
	next if (!$begin);
	$source = "#$1" if (/name="([^"]+)"/);

	if (/<p[^>]*>(.*)<\/p>/i) {
		$inter = $1;
		if ($inter =~ /<u>(Au cours[^<]*)<\/u>/) {
		    $aucours = $1;
		    if ($aucours =~ /\Wapr[^s]+s( |-)*midi($|\W)/) {
			$nb_seance = 2;
		    }elsif ($aucours =~ /\Wsoir(é|&[^;]*;)e($|\W)/) {
			$nb_seance = 3;
		    }
		    print_inter() if (!$is_newcontext);
		    $heure = ($nb_seance == 1) ? '1ere' : $nb_seance.'ieme';
		    $heure .= ' séance';
		    $timestamp = '0';
		}
		if($is_newcontext) {
		    $is_newcontext = 0;
		    print_inter();
		}
		$inter =~ s/<a[^>]*><\/a>//ig;
		if ($inter =~ /^<(u|strong|em)>(.*)<\/(u|strong|em)>$/i) {
			$inter = $2;
			print_inter();
	                $inter =~ s/<[^>]+>//g;
			setfonction($inter);
			$intervention = '<p>'.$inter.'</p>';
			next;
		}
		$inter =~ s/(<\/(strong|a)[^>]*>)+([\s,]*)(<\/?(strong)[^>]*>)+/$3/ig;
		if (($interstrong && $inter =~ /<(a|strong)[^>]*>($recointer[^<]+)<\/(a|strong)>/i) || 
		    (!$interstrong && ($inter =~ /(>)\s*($recointer[^<]{10}[^<\.]*)/))) {
			$tmpintervenant = $2;
			$tmpintervenant =~ s/<[^>]*>//g;
			if ($tmpintervenant =~ s/^Mm\./M./i || $tmpintervenant =~ s/^Mmes/Mme/i) {
				$tmpintervenant =~ s/ et /, /g;
				$tmpintervenant =~ s/^([^,]+)(,[^,]+)*,\s*([^,]*)\W*$/$1/g;
				$tmpfonction = $3;
				$fonctions{$tmpintervenant} = $tmpfonction;
			} elsif ($tmpintervenant =~ s/^([^,]+), ([^,]*).*/$1/g) {
				$tmpfonction = $2;
				$tmpfonction =~ s/\W+$//;
				$fonctions{$tmpintervenant} = $tmpfonction;
			}else{
			    if ($tmpintervenant =~ s/\s*l[ae].{1,6}(pr(&[^;]*;|é)sidente?)\s*/ /) {
				$tmpfonction = $1;
			    }else{
				$tmpfonction = $fonctions{$tmpintervenant};
			    }
			}
			print_inter() if ($tmpintervenant ne $intervenant);
			$intervenant = $tmpintervenant;
			$intervenant =~ s/[\s-_\.,;"'<>«»]+$//;
		        $fonction = $tmpfonction;
			$url_intervenant = $1 if ($inter =~ /href="([^"]+senfic\/[^"]+)"/i);
		}
		$inter =~ s/<[^>]+>//g;
		print_inter() if ($inter =~ /^La (com)?mission /);
		$sintervenant = $intervenant;
		$sintervenant =~ s/([\(\)\*])/\\$1/g;
		$sfonction = $fonction;
		$sfonction =~ s/([\(\)\*])/\\$1/g;
		$inter =~ s/^[^\w\&]*$sintervenant[^\w\&]*($sfonction[^\w\&]*|)//;
		$intervention .= '<p>'.$inter.'</p>' if ($inter =~ /[a-z]/i);
	}
#	print "$date $titre $source\n";
}
print_inter();
