<label for="dob-{$payment_mean['id']}">{s name="PaymentPlaceholderBirthday" namespace="frontend/paynl/plugins"}{/s}: </label>
<input type="text"
       id="dob-{$payment_mean['id']}"
       name="dob[{$payment_mean['id']}]"
       placeholder="{s name="PaymentPlaceholderBirthday" namespace="frontend/paynl/plugins"}{/s}"
       data-mindate="1900-1-1"
       data-maxdate="today"
       data-datepicker="true"
       data-mode="single"/>
<br>
