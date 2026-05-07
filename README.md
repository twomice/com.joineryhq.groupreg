# CiviCRM: Group Event Participant Registration (com.joineryhq.groupreg)

Provides event registration features for family and friends, even if registering user (e.g., parent) is not attending.

![Screenshot](/images/screenshot.png)

* Allows registering user to indicate whether they will attend the event or not.
* Non-attending registrants are assigned a distinct role (configurable on a
  per-event basis); the expectation is that you will have configured this role
  to be "un-counted" in terms of participant count.
* Provides list of related contacts as optional pre-filled additional participants.
* Related contacts may be related directly to the registering user, or (optionally)
  through a mutually related organization.
* Records all additional participants as related to user, with relationship type
  selected by user.

The extension is licensed under [GPL-3.0](LICENSE.txt).

## Usage

See [beta demo video here](https://www.youtube.com/watch?v=2zd3bENs0Ow&t=722s).

Summary of that video:
* Walk-through of user experience: I'm the father of 2 children who are attending
  a youth camp; I'm not attending, but I'm the one who is performing the registration.
* Examination of the CiviCRM records for Contacts, Participants and Payments, as
  created/updated through this user experience.
* Examination of the configuraion for the event:
  * _Event: Online Registration_ options
    * Hide "Not you" message?
    * Prompt for Additional Participant through relationships?
    * Require selection of existing contact for Additional Participants?
    * Primary participant is attendee
    * Non-attendee role
  * _Event: Fees_ options
    * Use of Price Sets to achieve "Hide from non-participating primary registrants?"
      behavior

Not mentioned in the video:
* "Hide from non-participating primary registrants?" behavior depends on the use
  of Price Sets. If event fees are configured _without_ Price Sets (i.e., through
  "Regular Fees", a.k.a. "quick config" fees), then all price options will be
  presented for all participants (including the non-attendning registrant).

## Available hooks

This extension offers the following hooks to manipulate some of its behaviors:

### hook_civicrm_groupreg_alterOrgs
This hook is called when the extension builds its list of related Organizations
which may be selected on "additional participant" forms -- which is displayed when
the event configuration option "Prompt for Additional Participant through relationships?"
is set to "Yes, through relationships to related organizations".

For example, hook implementations could remove or modify any contact in this array;
or add organizations to the array; or specify that certain organizations should
be displayed as disabled (un-selectable).

#### Definition
`hook_civicrm_groupreg_alterOrgs(array &$orgs, array &$disabledOrgCids)`

#### Parameters
- `$orgs`: An array of organization names, keyed to organization contact IDs.
- `$disabledOrgCids`: An array of organization contact IDs for organizations in
  `$orgs` which should be presented as disabled (un-selectable) in the Additional
  Participant form(s).

### hook_civicrm_groupreg_alterIndividuals
This hook is called when the extension builds its list of Individuals which may
be selected on "additional participant" forms -- which is displayed when the
event configuration option "Prompt for Additional Participant through relationships?"
is set to "Yes, through relationships to related organizations" or "Yes, through
direct relationships to primary participant".

For example, hook implementations could remove or modify any contact in this array;
or add individuals to the array.

#### Definition
`hook_civicrm_groupreg_alterIndividuals(array &$Individuals)`

#### Parameters
- `$individuals`: An array of API 4 results on a contact.get call. Each array
  element represents a contact. Notable keys in each contact array, which are
  defined and/or used by this extnsion (here, "relevant contact" is either the
  current user, or the selected organization, depending on the value of the
  event configuration option "Prompt for Additional Participant through relationships?"):
  - `rtype`: String. The name of the relationship type by which this individual
    is related to the relevant contact.
  - `relationship_type_id`: Integer. The system id of that relationship type.
  - `relationship_id`: Integer. The system id of the relationship by which this
    individual is related to the relevant contact.
  - `groupregDisabled`: Boolean. If true, this individual will be presented as
    disabled (un-selectable) in the Additional Participant form(s).

## Requirements

* PHP v7.0+
* CiviCRM 5.0

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl com.joineryhq.groupreg@https://github.com/twomice/com.joineryhq.groupreg/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git)
repo for this extension and install it with the command-line tool
[cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/twomice/com.joineryhq.groupreg.git
cv en groupreg
```

## Support

Support for this package is handled under Joinery's ["Active Support" policy](https://joineryhq.com/software-support-levels#active-support).

Public issue queue for this package: https://github.com/twomice/com.joineryhq.groupreg/issues
