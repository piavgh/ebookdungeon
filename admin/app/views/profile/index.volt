<h2>My Profile</h2>
<br>
<p>Username: {{ adminname }}</p>
<p>Email: {{ adminemail }}</p>
<br>
<div class="btn btn-default">{{ link_to('profile/changeemail', 'Change Email') }}</div>
<div class="btn btn-default">{{ link_to('profile/changepass', 'Change Password') }}</div>