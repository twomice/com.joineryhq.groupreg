# CiviCRM: Group Event Participant Registration (com.joineryhq.groupreg)

Provides event registration features for family and friends, even if registering user (e.g., parent) is not attending.

![Screenshot](/images/screenshot.png)

* Allows registering user to indicate whether they will attend the event or not.
* Provides list of related contacts as optional pre-filled additional participants.
* Related contacts may be related directly to the registering user, or (optionally) through a mutually related organization.
* Records all additional participants as related to user, with relationship type selected by user.

The extension is licensed under [GPL-3.0](LICENSE.txt).

## Usage

See beta demo video here: https://www.youtube.com/watch?v=2zd3bENs0Ow&t=722s

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

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/twomice/com.joineryhq.groupreg.git
cv en groupreg
```

