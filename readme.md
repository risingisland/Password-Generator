<div align="center">

# 🔑 Password Generator

**A lightweight, self-contained PHP password generator with a responsive web interface.**

[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![PHP 8.x](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Version](https://img.shields.io/badge/version-1.2-blue.svg)](https://github.com/risingisland)
[![No Dependencies](https://img.shields.io/badge/dependencies-none-success.svg)](#requirements)
[![License](https://img.shields.io/badge/license-not%20specified-lightgrey.svg)](#license)

<br>

**Generate • Customize • Preview • Export**

The script generates multiple passwords according to configurable character-set options, supports prefixes and suffixes, can optionally append SHA1 and/or MD5 hashes, and allows the generated list to be exported as TXT or CSV.
</div>

---

## Features

- PHP 7.4 / 8.x compatible
- No external libraries or dependencies
- Responsive, mobile-friendly interface
- Generate between **1 and 9,999 passwords** at a time
- Password lengths from **4 to 19 characters**
- Uppercase letters (`A-Z`)
- Lowercase letters (`a-z`)
- Numerals (`0-9`)
- Special symbols:
  - `? ! @ # $ % & *`
- Optional exclusion of visually confusing characters
- Custom additional characters/strings
- Optional prefix and suffix
- Optional SHA1 hash appended to each generated password
- Optional MD5 hash appended to each generated password
- Duplicate passwords are rejected
- Generation time and actual result count are displayed
- Export generated passwords as:
  - TXT
  - CSV
- Remembers the last-used generation settings during the PHP session
- Simple animated password preview
- Uses vanilla JavaScript; no jQuery required

## Requirements

- PHP **7.4 or newer**
- PHP sessions enabled
- A web server capable of running PHP

No database is required.

## Installation

1. Copy `password.php` to a directory served by your web server.
2. Make sure PHP is enabled for the directory.
3. Open the script in a browser.

For example:

```text
https://example.com/password.php
```

The script starts a PHP session automatically when required.

## Usage

Select the character types you want to use and configure the password length and number of passwords.

### General Options

| Option | Description |
|---|---|
| Upper case letters | Includes `A-Z` |
| Lower case letters | Includes `a-z` |
| Numerals | Includes `0-9` |
| Exclude dubious symbols | Removes characters that can easily be confused visually |
| Length | Password length, from 4 to 19 characters |
| Number of passwords | Number to generate, from 1 to 9,999 |
| Prefix | Optional prefix, maximum 4 characters |
| Suffix | Optional suffix, maximum 4 characters |

### Advanced Options

| Option | Description |
|---|---|
| Special symbols | Adds `?!@#$%&*` |
| SHA1 | Appends a SHA1 hash separated by `\|` |
| MD5 | Appends an MD5 hash separated by `\|` |
| Other | Adds custom space-separated values to the character pool |
| Simple preview | Generates a client-side visual preview independent of the main settings |

### Dubious Characters

When **Exclude dubious symbols** is enabled, the following characters are removed from the normal character pool:

```text
! 1 I i l O 0 o ^ , .
```

This is useful when passwords need to be read or entered manually.

## Password Generation

The generator builds a character pool from the selected options and randomly selects characters from that pool.

When character classes are enabled, the generated password is subsequently checked to ensure that the requested classes are actually represented. For example, if uppercase, lowercase and numerals are selected, the result must contain at least one character from each of those classes.

Duplicate generated passwords are also rejected.

The generator uses a safety limit of:

```text
(number of requested passwords × 200) + 1000
```

generation attempts to prevent an endless loop when the requested combination becomes difficult to satisfy.

## Prefixes and Suffixes

A prefix and suffix can be added to generated passwords.

Both fields are limited by the interface to **4 characters**.

The prefix is applied by replacing the beginning portion of the generated password, while the suffix replaces the corresponding ending portion. This means the final password may not have exactly the selected length when prefixes or suffixes are used.

## Hash Options

SHA1 and MD5 can optionally be appended to each generated password.

The output format is:

```text
password|hash
```

If both options are selected, the MD5 hash is calculated after the SHA1 output has already been appended, resulting in:

```text
password|sha1hash|md5hash
```

### Important Security Note

**SHA1 and MD5 are not suitable password-hashing algorithms for storing user passwords.**

These options are provided as optional output transformations and should not be interpreted as a secure password-storage mechanism. For applications that need to store passwords, use PHP's password API, such as `password_hash()` and `password_verify()`.

## Custom Characters / "Other"

The **Other** field accepts values separated by spaces.

For example:

```text
€ £ ¥ _ - =
```

These values are added to the character pool used during generation.

Because the input is split on spaces, a multi-character value can be added as a single pool item. This can therefore produce generated results containing more than one character from a single selection.

## Export

After passwords have been generated, the **Export** menu provides two formats.

### TXT

Downloads:

```text
pw.txt
```

Passwords are separated by CRLF line endings.

### CSV

Downloads:

```text
pw.csv
```

Each password is written on its own line.

The export functionality uses the password list stored in the current PHP session.

## Sessions

The script uses PHP sessions for two purposes:

1. **Remembering the last generation options**
2. **Temporarily storing the generated password list for export**

The generated password list is stored in:

```php
$_SESSION['pwd_list']
```

The last-used options are stored in:

```php
$_SESSION['last_options']
```

The session data is temporary and follows the normal lifecycle/configuration of PHP sessions.

## Security Considerations

This script is intended as a convenient web-based password generator, but there are several considerations for production use.

### Randomness

The main generator uses PHP's:

```php
array_rand()
```

This is convenient for general-purpose random selection, but it is **not a cryptographically secure random-number generator**.

If the generated passwords are intended for high-security or cryptographic purposes, the generation routine should be changed to use a cryptographically secure source such as PHP's `random_int()`.

### Transport Security

If the generator is exposed publicly, serve it over **HTTPS**.

Generated passwords are displayed in the browser and temporarily stored in the PHP session for export.

### Public Deployment

A publicly accessible password generator can be abused to generate large numbers of passwords or consume server resources. The script limits the requested number of passwords to 9,999 and also has a generation-attempt safety valve, but additional server-side rate limiting may be appropriate for an Internet-facing installation.

### Exported Files

Exported password lists are plain text files. Handle them carefully and delete them when they are no longer required.

## Interface

The interface is completely contained within the PHP file and includes:

- HTML
- CSS
- PHP processing
- Vanilla JavaScript

The design uses a dark interface with responsive two-column/one-column layouts.

At viewport widths below approximately **760px**, the main settings layout changes to a single column.

## JavaScript Preview

The **Simple preview** control is separate from the PHP generator.

It uses a fixed client-side character set:

```text
qwertyuiopasdfghjklzxcvbnm
QWERTYUIOPASDFGHJKLZXCVBNM
1234567890
?!@#$%&*
```

The preview always generates 10 characters and does not take the selected PHP generator settings into account.

The preview is intended as a visual/interactive convenience rather than as the password-generation mechanism.

---

## 📄 License

No explicit software license is specified in the source file.

If you plan to redistribute or substantially modify the project, confirm the applicable licensing terms with the author.

---

## 👤 Author

**risingisland**

[![GitHub](https://img.shields.io/badge/GitHub-risingisland-181717?logo=github&logoColor=white)](https://github.com/risingisland)

[View repositories](https://github.com/risingisland?tab=repositories)

---

## ☕ Support

If you find this project useful and would like to support its development:

[![Ko-fi](https://img.shields.io/badge/Support%20on-Ko--fi-ff5e5b?logo=ko-fi&logoColor=white)](https://ko-fi.com/ericmontgomery)

---

<div align="center">

**Password Generator v1.2**

Made with PHP • No frameworks • No database • No dependencies

</div>

