<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class provides the functionality to email a group of contacts.
 */
class CRM_Contribute_Form_Task_Email extends CRM_Contribute_Form_Task {
  use CRM_Contact_Form_Task_EmailTrait;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    CRM_Contact_Form_Task_EmailCommon::preProcessFromAddress($this);
    parent::preProcess();

    // we have all the contribution ids, so now we get the contact ids
    parent::setContactIDs();

    $this->assign('single', $this->_single);
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = array_merge(CRM_Core_SelectValues::contributionTokens(), $tokens);
    return $tokens;
  }

}
