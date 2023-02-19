<?php $nonav = 0;
include "../head.php";
?>
<div class="apply apply-sm">
	<div class="apply-head">
		<h2><?= Config::$name; ?> Staff Applications</h2>
	</div>
	<div class="apply-body">
		<label for="name">Full Name</label>
		<input id="name" type="text" placeholder="Full Name">
		<label for="age">Age</label>
		<input id="age" type="number" placeholder="Age">
		<label for="email">Email Address</label>
		<input id="email" type="text" placeholder="Email">
		<label for="discord">Discord Handle</label>
		<input id="discord" type="text" placeholder="MyDiscord#0000">
		<label for="timezone">Timezone</label>
		<input id="timezone" type="text" placeholder="Timezone (EST,PST,GMT)">
		<label for="about_me">About Me</label>
		<textarea id="about_me" placeholder="Tell us about yourself!"></textarea>
		<small>Please tell us about yourself, your experience, and why you want to join the staff team.</small>
		<label for="why_me">Why Me?</label>
		<textarea id="why_me" placeholder="Why should we choose you?"></textarea>
		<small>Please tell us why you should be chosen over other applicants.</small>
		<label for="experience">Experience</label>
		<textarea id="experience" placeholder="What experience do you have?"></textarea>
		<small>Please tell us your experience.</small>
	</div>
	<div class="apply-footer">
		<button onclick="SubmitApplication()">Submit Application</button>
	</div>
</div>
<script>
	function SubmitApplication() {
		apiclient.post(`/api/v2/staff/applications/submit`, {
			name: $('#name').val(),
			data: {
				age: $('#age').val(),
				email: $('#email').val(),
				discord: $('#discord').val(),
				timezone: $('#timezone').val(),
				age: $('#age').val(),
				about_me: $('#about_me').val(),
				why_me: $('#why_me').val(),
				experience: $('#experience').val(),
			}
		}).then(({
			data
		}) => {
			if (!data.success) {
				throw new Error(data.message)
			}

			new Noty({
				type: 'success',
				text: 'Application Submitted! Please allow up to one week for a response.'
			}).show();
		}).catch(noty_catch_error);
	}
</script>
<style>
	.apply {
		box-shadow: 0 8px 48px 0 rgba(0, 0, 0, 0.3);
		margin: 4em auto;
		padding: 0;
		-webkit-border-radius: 4px;
		-moz-border-radius: 4px;
		border-radius: 4px;
	}

	.apply h2 {
		text-align: left !important;
		margin: 0;
	}

	.apply-sm {
		width: 400px;
	}

	.apply-head {
		background-color: #1c1b30;
		padding: 20px;
		border-top-left-radius: 4px;
		border-top-right-radius: 4px;
	}

	.apply-body {
		background-color: #2e2c46;
	}

	.apply-body input {
		display: block;
		margin: 0;
		padding: 12px;
		background-color: #2e2c46;
		color: #fff;
		width: 100%;
		font-size: 14px;
		transition: 150ms;
		border-bottom: 1px #2c2f50 solid;
		box-shadow: 0 0 0 0 transparent;
	}

	.apply-body input:focus {
		border-bottom: 1px #fff solid;
	}

	.apply-footer button {
		width: 100%;
		height: 60px;
		margin: 0px;
		background-color: #2e2c46;
		border: none;
		cursor: pointer;
		transition: 200ms;
		border-bottom-left-radius: 4px;
		border-bottom-right-radius: 4px;
	}

	.apply-footer button:hover {
		background-color: #4e4c79;
	}

	.apply-body label,
	small {
		display: block;
		padding: 10px 8px;
		background-color: rgba(0, 0, 0, 0.1);
	}

	.apply-body small {
		color: #aaa;
	}

	.apply-body textarea {
		background-color: #2e2c46;
		width: 100%;
		border: none;
		padding: 12px;
		font-size: 14px;
		resize: vertical;
		min-height: 200px;
	}
</style>