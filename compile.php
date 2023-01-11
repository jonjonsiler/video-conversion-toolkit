<html>
	<head>
		<meta http-equiv="refresh" content="300" />
	</head>
	<body><?php

//header("Content-type: application/xml");

include_once('controllers/db.class.php');
include_once('./configure.php');
ini_set("display_errors",1);
$c = new Connection(
	EL_DATABASE,
	EL_PORT,
	EL_USER,
	EL_PASS
);
$c->setQuery("SELECT * FROM ENPSElectionRaceProperties WHERE RaceID IN (SELECT DISTINCT RaceID FROM ENPSElectionRaceResults) ORDER BY RaceID");
$races = $c->loadObjectList();
//print_r($races);



$xml = new XMLWriter();
//$xml->openMemory();
$xml->openUri('races.xml');
$xml->startDocument('1.0', 'utf-8');
$xml->setIndent(true);
$xml->setIndentString("\t");
$xml->startElement('election');
$xml->writeElement('title','Election Results by Race');
$xml->writeElement('creator','OETA');
$xml->startElement('meta');
$xml->writeAttribute("rel", "generator");
$xml->text('MetaMorphISIS at OETA');
$xml->endElement();
$xml->startElement('meta');
$xml->writeAttribute("rel", "generatorURL");
$xml->text('http://www.oeta.tv');
$xml->endElement();
	$xml->startElement("races");
	$xml->writeAttribute('count',count($races));
foreach ($races as $race) {
		$xml->startElement('race');
		$xml->writeAttribute('id', $race->RaceID);
		$xml->writeAttribute('precincts_reporting', $race->RacePrecinctsReporting);
		$xml->writeAttribute('precincts_total', $race->RacePrecinctsTotal);
		$xml->writeAttribute('precincts_percent', $race->RacePrecinctsPercent);
		$xml->writeAttribute('office_name',$race->RaceCGTitle1);
		$xml->writeAttribute('office_affiliation',$race->RaceCGTitle2);
		$c->setQuery('SELECT * FROM ENPSElectionRaceResults WHERE RaceID = '.$race->RaceID. " ORDER BY RaceCandidateVotes DESC");
		foreach ($c->loadObjectList() as $candidate) {
			$xml->startElement('candidate');
				$xml->writeAttribute('name', $candidate->RaceCandidateDisplayName);
				$xml->writeAttribute('incumbent', ord($candidate->RaceCandidateIncumbent));
				$xml->writeAttribute('leader', ord($candidate->RaceCandidateLeader ));
				$xml->writeAttribute('winner', ord($candidate->RaceCandidateWinner));
				$xml->writeAttribute('votes', (int)$candidate->RaceCandidateVotes);
				$xml->writeAttribute('percent', (float)$candidate->RaceCandidatePercentVotes);
			$xml->endElement();
		}

		$xml->endElement();
}
	$xml->endElement(); //end races
$xml->endElement(); //end playlist element
$xml->endDocument(); //end Document


$DOM = new DOMDocument();
$string= $xml->flush(true);

	$connection = ftp_connect('164.58.253.80');
	$login_result = @ftp_login($connection, 'oeta', 'voZq351MxF');
	if ($login_result){
		ftp_chdir($connection, 'www/main/data');
		if (ftp_put($connection, 'races.xml', 'races.xml', FTP_ASCII)){
			echo "file upload succeeded";
		} else {
			echo "file upload did not succeed";
		}
	} else echo $login_result;
	ftp_close($connection);

?>
	</body>
</html>