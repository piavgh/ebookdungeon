<h2>Change Maximum Space</h2>
<br><br>
<div class="well" style="max-width: 40%; padding: 8px 0;">
<ul class="nav nav-list">
    <li class="nav-header">User space info</li>
    <li class="nav nav-list">Maximum space : <span class="label label-info"><?php echo Utils::formatFileSize($user->maximum) ?></span></li>
    <li class="nav nav-list">Used space : <span class="label label-important"><?php echo Utils::formatFileSize($user->used) ?></span></li>
    <li class="nav nav-list">Available space : <span class="label label-success"><?php echo Utils::formatFileSize($user->available) ?></span></li>
    <br><br>
    {{ form('account/updatemaximum', 'class': 'form-inline', 'method': 'post') }}
    	<li><input type="hidden" name="user_id" value="<?php echo $user->user_id ?>">
    	<input type="text" id="new_maximum" name="new_maximum" placeholder="New maximum space (MB)">
    	<button type="submit" class="btn btn-success">Update</button></li>
	</form>
</ul>
</div>