<input type="text"
       name="dob[{$payment_mean['id']}]"
       placeholder="{s name="PaymentPlaceholderBirthday" namespace="frontend/paynl/plugins"}{/s}"
       data-mindate="1900-1-1"
       data-defaultDate="{"-20 years"|date_format:"%Y-%m-%d"}"
       data-maxdate="today"
       data-datepicker="true"
       data-mode="single"/>
<br>
