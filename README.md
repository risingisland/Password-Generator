<div align="center">

# 🔑 Password Generator

**A lightweight, self-contained PHP password generator with a responsive web interface.**

[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![PHP 8.x](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Version](https://img.shields.io/badge/version-1.3-blue.svg)](https://github.com/risingisland)
[![No Dependencies](https://img.shields.io/badge/dependencies-none-success.svg)](#requirements)
[![CSPRNG](https://img.shields.io/badge/CSPRNG-random__int()-brightgreen.svg)](#cryptographically-secure-generation)
[![License](https://img.shields.io/badge/license-not%20specified-lightgrey.svg)](#-license)

<br>

**Generate • Customize • Preview • Export • Estimate Strength**

The script generates multiple passwords according to configurable character-set options, supports prefixes and suffixes, can optionally append SHA1 and/or MD5 hashes, estimates the strength of each generated password, and allows the generated list to be exported as TXT or CSV.
</div>

---

## Features

- PHP 7.4 / 8.x compatible
- No external libraries or dependencies
- **Cryptographically secure password generation via `random_int()` (CSPRNG)**
- **Password strength estimation (entropy in bits + qualitative label)**
- **Per-password strength table and aggregate summary**
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
- Simple animated password preview (also uses CSPRNG in the browser)
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

## Cryptographically Secure Generation

As of v1.3, characters are drawn from the pool using PHP's `random_int()`:

```php 
$chars[] = $charPool[random_int(0, $poolSize - 1)];
```

`random_int()` is the recommended CSPRNG function in modern PHP. It is backed by the operating system's cryptographically secure source:

`getrandom(2) on Linux`

`/dev/urandom on Unix-like systems`

`CryptGenRandom / BCryptGenRandom on Windows`

Unlike `rand()`, `mt_rand()`, or `array_rand()`, the output is not predictable from prior values, which makes it suitable for generating passwords intended for real-world use.

The browser-side Simple preview also uses the Web Crypto API (`crypto.getRandomValues()`) with rejection sampling to avoid modulo bias, falling back to `Math.random()` only on very old browsers that lack crypto.

## Password Strength Estimation

As of v1.3, the script estimates the strength of each generated password.
How It Works

For each password, the script computes the entropy of the random portion in bits:

```
bits = (number of random characters) × log₂(pool size)
```

Where:

- pool size is the number of distinct characters (and custom "other" strings) available for selection after all exclusions have been applied

- number of random characters is the count of characters that survive after prefix/suffix overwriting (the prefix and suffix replace the corresponding beginning/end of the random body, so they do not contribute to the random portion)

Prefix and suffix strings are treated as zero-entropy (public) values, since they are typically fixed strings chosen by the user.

## Qualitative Labels

The raw bit count is mapped to a label:

| Entropy (bits) | Lable      |
| -------------- | ---------- |
| < 28           | Very Weak  |
| 28 – 35.9      | Weak       |
| 36 – 59.9      | Reasonable |
| 60 – 79.9      | Strong     |
| ≥ 80           | Very Strong|

These thresholds are approximate and follow common guidance (roughly NIST-flavored). They are meant as a quick visual guide, not a formal security audit.
Where Strength Is Shown

- Aggregate summary — displayed above the results, with the average, minimum, and maximum bits across the generated batch

- Per-password table — a collapsible <details> section listing every password with its individual label and bit count

## Caveats

- Entropy ≠ real-world strength. A random 10-character string drawn from a 62-character pool has the same estimated entropy regardless of what it actually spells. A full estimator like zxcvbn would catch dictionary words, keyboard patterns, and common substitutions — but it is a ~800 KB library and conflicts with the "self-contained single file" design goal of this project.

- Custom "other" strings are merged into the pool, so they do increase the estimated pool size. This is a simplification: adding a single multi-character string like secret technically increases the pool by one item, but the string itself is not random.

- SHA1/MD5 appends are not counted as adding entropy. They are deterministic transformations of the already-generated password.

## Prefixes and Suffixes

A prefix and suffix can be added to generated passwords.

Both fields are limited by the interface to **4 characters**.

The prefix is applied by replacing the beginning portion of the generated password, while the suffix replaces the corresponding ending portion. This means the final password may not have exactly the selected length when prefixes or suffixes are used.

Because prefixes and suffixes overwrite random characters, they reduce the entropy of the final password. The strength estimation accounts for this by counting only the random characters that survive in the final body.

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
random_int()
```

which is a cryptographically secure pseudorandom number generator (CSPRNG) backed by the operating system's entropy source. This replaces the earlier use of array_rand(), which was not cryptographically secure.

The browser-side preview also uses crypto.getRandomValues() where available.

### Transport Security

If the generator is exposed publicly, serve it over **HTTPS**.

Generated passwords are displayed in the browser and temporarily stored in the PHP session for export.

### Public Deployment

A publicly accessible password generator can be abused to generate large numbers of passwords or consume server resources. The script limits the requested number of passwords to 9,999 and also has a generation-attempt safety valve, but additional server-side rate limiting may be appropriate for an Internet-facing installation.

### Exported Files

Exported password lists are plain text files. Handle them carefully and delete them when they are no longer required.
Strength Estimation Is a Guide, Not a Guarantee

The strength labels produced by this script are based on a simple entropy model. They do not account for dictionary words, keyboard walks, leetspeak substitutions, or other real-world weaknesses. Do not treat a "Very Strong" label as a formal security certification.

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

The preview is intended as a visual/interactive convenience rather than as the password-generation mechanism. Since v1.3, it draws characters via crypto.getRandomValues() with rejection sampling, falling back to Math.random() only when the Web Crypto API is unavailable.

## Changelog
v1.3

- Cryptographically secure generation — replaced array_rand() with random_int() in the server-side generator, and Math.random() with crypto.getRandomValues() in the browser preview.

- Password strength estimation — added entropy calculation (bits) and qualitative labels (Very Weak → Very Strong).

- Aggregate strength summary displayed above the results (average / min / max bits across the batch).

- Per-password strength table in a collapsible <details> section.

- Prefix/suffix entropy accounting — the strength estimate now correctly reduces the effective random length when a prefix or suffix overwrites part of the random body.

- Fixed `<option value="...">` on the length `<select>` (previously relied on implicit text content as the value).

- Initialized $generationError explicitly to avoid an undefined-variable edge case.

- Added X-Content-Type-Options: nosniff to TXT/CSV export responses.
    
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

