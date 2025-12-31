<?php

/**
 * Initialize the PluginDeliverytermsGenerate object.
 * This class likely contains the core logic for PDF generation, 
 * database interactions for documents, and mailing functions.
 */
$PluginDeliverytermsGenerate = new PluginDeliverytermsGenerate();

/**
 * Action: Generate Protocol
 * Triggered when the 'generate' button/request is submitted.
 * Calls the static method makeProtocol() to create a new document (usually a PDF).
 * After execution, it redirects the user to the previous page using Html::back().
 */
if (isset($_REQUEST['generate'])) {
	$PluginDeliverytermsGenerate::makeProtocol();
	Html::back();
}

/**
 * Action: Delete Documents
 * Triggered when the 'delete' request is received.
 * Calls deleteDocs() to remove generated files or database records associated with protocols.
 * Redirects the user back to maintain the workflow flow.
 */
if (isset($_REQUEST['delete'])) {
	$PluginDeliverytermsGenerate::deleteDocs();
	Html::back();
}

/**
 * Action: Send Email
 * Triggered when a request to 'send' a specific protocol via email is made.
 * Calls sendOneMail() to handle the SMTP logic and attachment delivery.
 * Returns the user to the previous screen upon completion.
 */
if (isset($_REQUEST['send'])) {
	$PluginDeliverytermsGenerate::sendOneMail();
	Html::back();
}

?>