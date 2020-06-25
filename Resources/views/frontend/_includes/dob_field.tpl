<input type="text"
       name="dob[{$payment_mean['id']}]"
       placeholder="{s name="PaymentPlaceholderBirthday" namespace="frontend/paynl/plugins"}{/s}"
       data-mindate="1900-1-1"
       data-maxdate="{"-20 years"|date_format:"%Y-%m-%d"}"
       data-datepicker="true"
       data-mode="single"/>
<br>
