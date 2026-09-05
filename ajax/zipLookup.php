<?php
/*
 * OpenCATS
 * AJAX Street/City/State lookup via Zip Interface
 */
include_once(__DIR__ . '/bootstrap.php');

include_once(LEGACY_ROOT . '/lib/ZipLookup.php');
include_once(LEGACY_ROOT . '/lib/StringUtility.php');

$interface = new SecureAJAXInterface();

if (!isset($_REQUEST['zip']))
{
    $interface->outputXMLErrorPage(-1, 'Invalid zip code.');
    die();
}

$zip = $_REQUEST['zip'];
$country = isset($_REQUEST['country']) ? $_REQUEST['country'] : '';

$zipLookup = new ZipLookup();

$searchableZip = $zipLookup->makeSearchableUSZip($zip);

$data = $zipLookup->getCityStateByZip($searchableZip, $country);

$street = $data[1];
$city  = $data[2];
$state = $data[3];

/* Send back the XML data. */
$interface->outputXMLPage(
    "<data>\n" .
    "    <errorcode>0</errorcode>\n" .
    "    <errormessage></errormessage>\n" .
    "    <address>" . htmlspecialchars($street, ENT_QUOTES | ENT_SUBSTITUTE, AJAX_ENCODING) . "</address>\n" .
    "    <city>"    . htmlspecialchars($city, ENT_QUOTES | ENT_SUBSTITUTE, AJAX_ENCODING)   . "</city>\n" .
    "    <state>"   . htmlspecialchars($state, ENT_QUOTES | ENT_SUBSTITUTE, AJAX_ENCODING)  . "</state>\n" .
    "</data>\n"
);
?>
