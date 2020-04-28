-- +--------------------------------------------------------------------+
-- | CiviCRM version 5                                                  |
-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC (c) 2004-2019                                |
-- +--------------------------------------------------------------------+
-- | This file is a part of CiviCRM.                                    |
-- |                                                                    |
-- | CiviCRM is free software; you can copy, modify, and distribute it  |
-- | under the terms of the GNU Affero General Public License           |
-- | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
-- |                                                                    |
-- | CiviCRM is distributed in the hope that it will be useful, but     |
-- | WITHOUT ANY WARRANTY; without even the implied warranty of         |
-- | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
-- | See the GNU Affero General Public License for more details.        |
-- |                                                                    |
-- | You should have received a copy of the GNU Affero General Public   |
-- | License and the CiviCRM Licensing Exception along                  |
-- | with this program; if not, contact CiviCRM LLC                     |
-- | at info[AT]civicrm[DOT]org. If you have questions about the        |
-- | GNU Affero General Public License or the licensing of CiviCRM,     |
-- | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
-- +--------------------------------------------------------------------+
--
-- Generated from schema.tpl
-- DO NOT EDIT.  Generated by CRM_Core_CodeGen
--


-- +--------------------------------------------------------------------+
-- | CiviCRM version 5                                                  |
-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC (c) 2004-2019                                |
-- +--------------------------------------------------------------------+
-- | This file is a part of CiviCRM.                                    |
-- |                                                                    |
-- | CiviCRM is free software; you can copy, modify, and distribute it  |
-- | under the terms of the GNU Affero General Public License           |
-- | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
-- |                                                                    |
-- | CiviCRM is distributed in the hope that it will be useful, but     |
-- | WITHOUT ANY WARRANTY; without even the implied warranty of         |
-- | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
-- | See the GNU Affero General Public License for more details.        |
-- |                                                                    |
-- | You should have received a copy of the GNU Affero General Public   |
-- | License and the CiviCRM Licensing Exception along                  |
-- | with this program; if not, contact CiviCRM LLC                     |
-- | at info[AT]civicrm[DOT]org. If you have questions about the        |
-- | GNU Affero General Public License or the licensing of CiviCRM,     |
-- | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
-- +--------------------------------------------------------------------+
--
-- Generated from drop.tpl
-- DO NOT EDIT.  Generated by CRM_Core_CodeGen
--
-- /*******************************************************
-- *
-- * Clean up the exisiting tables
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `civicrm_groupreg_price_field`;
DROP TABLE IF EXISTS `civicrm_groupreg_event`;

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

-- /*******************************************************
-- *
-- * civicrm_groupreg_event
-- *
-- * Stores per-event settings for groupreg extension
-- *
-- *******************************************************/
CREATE TABLE `civicrm_groupreg_event` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Uniq identifier',
     `event_id` int unsigned NOT NULL   COMMENT 'FK to Event',
     `is_hide_not_you` tinyint NOT NULL  DEFAULT 0 ,
     `related_contact_tag_id` int unsigned NULL  DEFAULT NULL COMMENT 'FK to Tag',
     `is_prompt_related` int unsigned NOT NULL  DEFAULT 0 ,
     `is_primary_attending` int unsigned NOT NULL  DEFAULT 1 ,
     `nonattendee_role_id` int unsigned NOT NULL  DEFAULT 1 COMMENT 'Pseudo-FK to civicrm_option_value for participant_role' 
,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_groupreg_event_event_id FOREIGN KEY (`event_id`) REFERENCES `civicrm_event`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_groupreg_event_related_contact_tag_id FOREIGN KEY (`related_contact_tag_id`) REFERENCES `civicrm_tag`(`id`) ON DELETE SET NULL  
)    ;

-- /*******************************************************
-- *
-- * civicrm_groupreg_price_field
-- *
-- * Stores per-field settings for groupreg extension
-- *
-- *******************************************************/
CREATE TABLE `civicrm_groupreg_price_field` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique GroupregPriceField ID',
     `price_field_id` int unsigned NOT NULL   COMMENT 'FK to Price Field',
     `is_hide_non_participant` tinyint NOT NULL  DEFAULT 0  
,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_groupreg_price_field_price_field_id FOREIGN KEY (`price_field_id`) REFERENCES `civicrm_price_field`(`id`) ON DELETE CASCADE  
)    ;

 
