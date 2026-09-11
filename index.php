<?php
/**
 * Script Name: Password Generator
 * Description: A lightweight, self-contained PHP password generator.
 * Version: v1.2
 * PHP 7.4 / 8.x compatible.
 * Author: risingisland
 * Author URI: https://github.com/risingisland?tab=repositories
 * Donate URI: https://ko-fi.com/ericmontgomery
 */

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

/* -------------------------------------------------------------------
 * Export handler 
 * ---------------------------------------------------------------- */
if (isset($_GET['export']) && isset($_SESSION['pwd_list'])) {
	$format = $_GET['export'];

	if ($format === 'txt') {
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="pw.txt"');
		header('Pragma: no-cache');
		echo implode("\r\n", $_SESSION['pwd_list']);
		exit;
	}

	if ($format === 'csv') {
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="pw.csv"');
		header('Pragma: no-cache');
		echo implode("\n", $_SESSION['pwd_list']);
		exit;
	}
}

/* -------------------------------------------------------------------
 * Password generation 
 * ---------------------------------------------------------------- */
$passwords = [];
$generationTime = null;

function h($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$formSubmitted = isset($_POST['form_submitted']);
$lastOptions = $_SESSION['last_options'] ?? null;

// Checkbox state
function field_checked(string $key, bool $defaultOn): bool
{
	global $formSubmitted, $lastOptions;
	if ($formSubmitted) {
		return isset($_POST[$key]);
	}
	if ($lastOptions !== null) {
		return !empty($lastOptions[$key]);
	}
	return $defaultOn;
}

// Text/select field value: same fallback chain as field_checked().
function field_value(string $key, $default)
{
	global $formSubmitted, $lastOptions;
	if ($formSubmitted) {
		return $_POST[$key] ?? $default;
	}
	if ($lastOptions !== null && isset($lastOptions[$key])) {
		return $lastOptions[$key];
	}
	return $default;
}

if ($formSubmitted) {
	// Remember this submission
	$_SESSION['last_options'] = [
		'upper'	=> isset($_POST['upper']),
		'low'	=> isset($_POST['low']),
		'numerals' => isset($_POST['numerals']),
		'dubious'=> isset($_POST['dubious']),
		'simv'	 => isset($_POST['simv']),
		'sha1'	 => isset($_POST['sha1']),
		'md5'	=> isset($_POST['md5']),
		'length' => $_POST['length'] ?? 10,
		'num'	=> $_POST['num'] ?? 20,
		'prefix' => $_POST['prefix'] ?? '',
		'suffix' => $_POST['suffix'] ?? '',
		'other'	=> $_POST['other'] ?? '',
	];
}

if ($formSubmitted) {
	$length = isset($_POST['length']) ? max(1, (int) $_POST['length']) : 10;
	$count= isset($_POST['num']) ? (int) $_POST['num'] : 20;
	$count= max(1, min($count, 9999)); // hard cap, same as original

	$useLower	= isset($_POST['low']);
	$useUpper	= isset($_POST['upper']);
	$useNumerals = isset($_POST['numerals']);
	$useSpecial= isset($_POST['simv']);
	$excludeDubious = isset($_POST['dubious']);
	$useSha1 = isset($_POST['sha1']);
	$useMd5= isset($_POST['md5']);
	$prefix= isset($_POST['prefix']) ? trim($_POST['prefix']) : '';
	$suffix= isset($_POST['suffix']) ? trim($_POST['suffix']) : '';

	$otherWords = [];
	if (!empty($_POST['other'])) {
		$otherWords = array_filter(explode(' ', $_POST['other']), 'strlen');
	}

	$charPool = [];
	if ($useLower)	$charPool = array_merge($charPool, range('a', 'z'));
	if ($useUpper)	$charPool = array_merge($charPool, range('A', 'Z'));
	if ($useNumerals) $charPool = array_merge($charPool, range('0', '9'));
	if ($useSpecial)$charPool = array_merge($charPool, ['?', '!', '@', '#', '$', '%', '&', '*']);
	if ($excludeDubious) {
		$dubious = ['!', '1', 'I', 'i', 'l', 'O', '0', 'o', '^', ',', '.'];
		$charPool = array_diff($charPool, $dubious);
	}
	if (!empty($otherWords)) {
		$charPool = array_merge($charPool, $otherWords);
	}
	$charPool = array_values($charPool);

	if (empty($charPool)) {
		$generationError = 'Select at least one character set.';
	} else {
		set_time_limit(0);
		$startTime = microtime(true);
		$poolSize = count($charPool);
		$attempts = 0;
		$maxAttempts = $count * 200 + 1000; // safety valve against infinite loops

		while (count($passwords) < $count && $attempts < $maxAttempts) {
			$attempts++;

			$chars = [];
			for ($n = 0; $n < $length; $n++) {
				$chars[] = $charPool[array_rand($charPool)];
			}
			$pwd = implode('', $chars);

			// Re-validate that required character classes actually made it in.
			if ($useNumerals && !preg_match('/[0-9]/', $pwd)) continue;
			if ($useLower	&& !preg_match('/[a-z]/', $pwd)) continue;
			if ($useUpper	&& !preg_match('/[A-Z]/', $pwd)) continue;
			if ($useSpecial&& !preg_match('/[?!@#$%&*]/', $pwd)) continue;
			if (in_array($pwd, $passwords, true)) continue;

			if ($prefix !== '') {
				$pwd = $prefix . substr($pwd, 0, max(0, $length - strlen($prefix)));
			}
			if ($suffix !== '') {
				$keep = max(0, strlen($pwd) - strlen($suffix));
				$pwd = substr($pwd, 0, $keep) . $suffix;
			}
			if ($useSha1) $pwd .= '|' . sha1($pwd);
			if ($useMd5)$pwd .= '|' . md5($pwd);

			$passwords[] = $pwd;
		}

		$generationTime = microtime(true) - $startTime;
		$_SESSION['pwd_list'] = $passwords;
	}
} else {
	$length = 10;
	$count = 20;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Password Generator</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root {
	--bg: #0f1115;
	--panel: #171a21;
	--panel-border: #262b35;
	--text: #e7e9ee;
	--muted: #9aa1ad;
	--accent: #5b8cff;
	--accent-dark: #3f6ae0;
	--danger: #e05263;
	--radius: 10px;
	font-size: 16px;
}
* { box-sizing: border-box; }
body {
	margin: 0;
	background: var(--bg);
	color: var(--text);
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
	line-height: 1.45;
}
header.topbar {
	background: var(--panel);
	border-bottom: 1px solid var(--panel-border);
	padding: 1rem 1.5rem;
}
header.topbar h1 {
	margin: 0;
	font-size: 1.25rem;
	font-weight: 600;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}
header.topbar h1 .key {
	color: var(--accent);
}
main {
	max-width: 1000px;
	margin: 2rem auto;
	padding: 0 1.5rem 3rem;
}
.grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 1.25rem;
}
@media (max-width: 760px) {
	.grid { grid-template-columns: 1fr; }
}
.panel {
	background: var(--panel);
	border: 1px solid var(--panel-border);
	border-radius: var(--radius);
	padding: 1.25rem;
}
.panel h2 {
	margin: 0 0 1rem;
	font-size: 1rem;
	font-weight: 600;
	color: var(--muted);
	text-transform: uppercase;
	letter-spacing: 0.04em;
}
.field-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 1rem;
	padding: 0.55rem 0;
	border-bottom: 1px solid var(--panel-border);
}
.field-row:last-child { border-bottom: none; }
.field-row label {
	font-size: 0.92rem;
}
.hint {
	display: block;
	color: var(--muted);
	font-size: 0.78rem;
}
.two-col {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 0.75rem;
	margin-top: 0.75rem;
}
.field-block label {
	display: block;
	font-size: 0.85rem;
	color: var(--muted);
	margin-bottom: 0.3rem;
}
input[type="text"], input[type="number"], select, textarea {
	width: 100%;
	background: #0f1115;
	color: var(--text);
	border: 1px solid var(--panel-border);
	border-radius: 6px;
	padding: 0.45rem 0.6rem;
	font-size: 0.9rem;
	font-family: inherit;
}
textarea { resize: vertical; }
input:focus, select:focus, textarea:focus, button:focus {
	outline: 2px solid var(--accent);
	outline-offset: 1px;
}

/* Toggle switch */
.switch {
	position: relative;
	width: 42px;
	height: 24px;
	flex: none;
}
.switch input {
	position: absolute;
	opacity: 0;
	width: 100%;
	height: 100%;
	margin: 0;
	cursor: pointer;
	z-index: 1;
}
.switch .track {
	position: absolute;
	inset: 0;
	background: #2b3040;
	border-radius: 999px;
	transition: background 0.15s ease;
	pointer-events: none;
}
.switch .thumb {
	position: absolute;
	top: 3px; left: 3px;
	width: 18px; height: 18px;
	background: #fff;
	border-radius: 50%;
	transition: transform 0.15s ease;
	pointer-events: none;
}
.switch input:checked + .track { background: var(--accent); }
.switch input:checked + .track + .thumb { transform: translateX(18px); }

.simple-row {
	display: flex;
	gap: 0.5rem;
	margin-top: 0.75rem;
}
.simple-row input { flex: 1; }

button, .btn {
	font-family: inherit;
	font-size: 0.9rem;
	border-radius: 6px;
	border: 1px solid transparent;
	cursor: pointer;
	padding: 0.5rem 1rem;
}
.btn-primary {
	background: var(--accent);
	color: #fff;
}
.btn-primary:hover { background: var(--accent-dark); }
.btn-icon {
	background: #232733;
	color: var(--text);
	border: 1px solid var(--panel-border);
	padding: 0.45rem 0.6rem;
}
.btn-icon:hover { background: #2b3040; }

.actions {
	margin-top: 1.5rem;
	display: flex;
	align-items: center;
	gap: 0.75rem;
	flex-wrap: wrap;
}
.export-menu { position: relative; display: inline-block; }
.export-menu .btn-primary { display: flex; align-items: center; gap: 0.35rem; }
.export-menu-list {
	display: none;
	position: absolute;
	top: calc(100% + 4px);
	left: 0;
	background: var(--panel);
	border: 1px solid var(--panel-border);
	border-radius: 6px;
	min-width: 100px;
	z-index: 10;
	overflow: hidden;
}
.export-menu.open .export-menu-list { display: block; }
.export-menu-list a {
	display: block;
	padding: 0.5rem 0.75rem;
	color: var(--text);
	text-decoration: none;
	font-size: 0.85rem;
}
.export-menu-list a:hover { background: #232733; }

.results {
	margin-top: 1.5rem;
	background: var(--panel);
	border: 1px solid var(--panel-border);
	border-radius: var(--radius);
	padding: 1.25rem;
}
.results textarea {
	width: 100%;
	min-height: 260px;
	font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
	font-size: 0.85rem;
}
.meta {
	margin-top: 0.6rem;
	color: var(--muted);
	font-size: 0.8rem;
}
.error {
	color: var(--danger);
	font-size: 0.85rem;
	margin-top: 0.75rem;
}
footer.pagefoot {
	text-align: center;
	color: var(--muted);
	font-size: 0.78rem;
	padding: 2rem 0 1rem;
}
footer.pagefoot a { color: var(--muted); }
</style>
</head>
<body>

<header class="topbar">
<h1><span class="key">🔑</span> Password Generator</h1>
</header>

<main>
<form action="" method="post">
	<input type="hidden" name="form_submitted" value="1">
	<div class="grid">

	<section class="panel">
		<h2>General</h2>

		<div class="field-row">
		<label for="upper">Upper case letters (A-Z)</label>
		<span class="switch">
			<input id="upper" type="checkbox" name="upper" <?= field_checked('upper', true) ? 'checked' : '' ?>>
			<span class="track"></span><span class="thumb"></span>
		</span>
		</div>

		<div class="field-row">
		<label for="low">Lower case letters (a-z)</label>
		<span class="switch">
			<input id="low" type="checkbox" name="low" <?= field_checked('low', true) ? 'checked' : '' ?>>
			<span class="track"></span><span class="thumb"></span>
		</span>
		</div>

		<div class="field-row">
		<label for="numerals">Numerals (0-9)</label>
		<span class="switch">
			<input id="numerals" type="checkbox" name="numerals" <?= field_checked('numerals', true) ? 'checked' : '' ?>>
			<span class="track"></span><span class="thumb"></span>
		</span>
		</div>

		<div class="field-row">
		<div>
			<label for="dubious">Exclude dubious symbols</label>
			<span class="hint">!, 1, I, i, l, O, 0, o, ^, comma, dot</span>
		</div>
		<span class="switch">
			<input id="dubious" type="checkbox" name="dubious" <?= field_checked('dubious', true) ? 'checked' : '' ?>>
			<span class="track"></span><span class="thumb"></span>
		</span>
		</div>

		<div class="two-col">
		<div class="field-block">
			<label for="length">Length</label>
			<select id="length" name="length">
			<?php $selLength = (int) field_value('length', 10); ?>
			<?php for ($i = 4; $i < 20; $i++): ?>
				<option <?= $i == $selLength ? 'selected' : '' ?>><?= $i ?></option>
			<?php endfor; ?>
			</select>
		</div>
		<div class="field-block">
			<label for="num">Number of passwords</label>
			<input type="number" id="num" name="num" min="1" max="9999" value="<?= h(field_value('num', 20)) ?>">
		</div>
		<div class="field-block">
			<label for="prefix">Prefix</label>
			<input type="text" id="prefix" name="prefix" maxlength="4" value="<?= h(field_value('prefix', '')) ?>">
		</div>
		<div class="field-block">
			<label for="suffix">Suffix</label>
			<input type="text" id="suffix" name="suffix" maxlength="4" value="<?= h(field_value('suffix', '')) ?>">
		</div>
		</div>
	</section>

	<section class="panel">
		<h2>Advanced</h2>

		<div class="field-row">
		<div>
			<label for="simv">Special symbols</label>
			<span class="hint">?!@#$%&amp;*</span>
		</div>
		<span class="switch">
			<input id="simv" type="checkbox" name="simv" <?= field_checked('simv', true) ? 'checked' : '' ?>>
			<span class="track"></span><span class="thumb"></span>
		</span>
		</div>

		<div class="field-row">
		<div>
			<label for="sha1">SHA1</label>
			<span class="hint">appended, separated by "|"</span>
		</div>
		<span class="switch">
			<input id="sha1" type="checkbox" name="sha1" <?= field_checked('sha1', false) ? 'checked' : '' ?>>
			<span class="track"></span><span class="thumb"></span>
		</span>
		</div>

		<div class="field-row">
		<div>
			<label for="md5">MD5</label>
			<span class="hint">appended, separated by "|"</span>
		</div>
		<span class="switch">
			<input id="md5" type="checkbox" name="md5" <?= field_checked('md5', false) ? 'checked' : '' ?>>
			<span class="track"></span><span class="thumb"></span>
		</span>
		</div>

		<div class="field-block" style="margin-top:0.75rem">
		<label for="other">Other <span class="hint" style="display:inline">(symbols separated by space)</span></label>
		<textarea id="other" name="other" rows="2"><?= h(field_value('other', '')) ?></textarea>
		</div>

		<div class="field-block" style="margin-top:1rem">
		<label for="pass">Simple preview <span class="hint" style="display:inline">(ignores settings above)</span></label>
		<div class="simple-row">
			<input type="text" id="pass" readonly value="">
			<button type="button" class="btn-icon" onclick="animatePreview('pass', 10)" title="Generate preview">⟳</button>
		</div>
		</div>
	</section>
	</div>

	<div class="actions">
	<button type="submit" class="btn btn-primary">Generate</button>
	<?php if (!empty($passwords)): ?>
		<div class="export-menu" id="exportMenu">
		<button type="button" class="btn btn-primary" onclick="document.getElementById('exportMenu').classList.toggle('open')">
			Export ▾
		</button>
		<div class="export-menu-list">
			<a href="?export=txt">txt</a>
			<a href="?export=csv">csv</a>
		</div>
		</div>
	<?php endif; ?>
	</div>
</form>

<?php if (isset($generationError)): ?>
	<p class="error"><?= h($generationError) ?></p>
<?php elseif (!empty($passwords)): ?>
	<section class="results">
	<textarea readonly><?= h(implode("\n", $passwords)) ?></textarea>
	<p class="meta">
		<strong>Count:</strong> <?= count($passwords) ?>
		&nbsp;&nbsp;<strong>Time:</strong> <?= number_format($generationTime, 4) ?>s
	</p>
	</section>
<?php endif; ?>
</main>

<footer class="pagefoot">
&copy; <span id="year"></span>
<a href="https://github.com/risingisland?tab=repositories" target="_blank" rel="noopener">risingisland</a> Password Generator v1.2
</footer>

<script>
document.getElementById('year').textContent = new Date().getFullYear();

// Close the export dropdown when clicking outside it.
document.addEventListener('click', function (e) {
	var menu = document.getElementById('exportMenu');
	if (menu && !menu.contains(e.target)) {
	menu.classList.remove('open');
	}
});

// Preview effect.
function animatePreview(fieldId, length) {
	var chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890?!@#$%&*';
	var field = document.getElementById(fieldId);
	var result = '';
	for (var i = 0; i < length; i++) {
	result += chars.charAt(Math.floor(Math.random() * chars.length));
	}
	field.value = '';
	var i = 0;
	var interval = setInterval(function () {
	field.value = result.slice(0, i + 1).split('').map(function (c, idx) {
		return idx === i ? chars.charAt(Math.floor(Math.random() * chars.length)) : c;
	}).join('');
	i++;
	if (i >= length) {
		clearInterval(interval);
		field.value = result;
	}
	}, 35);
}
</script>
</body>
</html>