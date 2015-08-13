<?php
include_once 'bootstrap.php';

$module = new \SAP_Module_BapiBupaExistenceCheck();
$importer = new SAP_Import_BapiBupaExistenceCheck_Businesspartner();
$importer->nameless = '0002000014';
$module->addImport($importer);
$data = $module->get();
var_dump($data);

$module = new SAP_Module_BapiCtraccontractaccountGd1();
$importer = new SAP_Import_BapiCtraccontractaccountGd1_Businesspartner();
$importer->nameless = '0002000014';
$module->addImport($importer);

$importer = new SAP_Import_BapiCtraccontractaccountGd1_Contractaccount();
$importer->nameless = '000002000014';
$module->addImport($importer);

$data = $module->get();
var_dump($data);
exit;
