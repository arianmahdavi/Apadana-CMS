<div id="newsletter-ajax"></div>
<form id="newsletter-form">
<div id="startform" >
عنوان خبرنامه:&nbsp;<input name="newsletter[title]" type="text" value="" lang="fa-IR" id="title" style="width:70%" /><br/><br/>
{textarea}<br/>
<input type="hidden" name="newsletter[total]" value="0" id ="total">
نوع ارسال : <input type="radio" name="newsletter[type]" value="pm" />پیام خصوصی &nbsp&nbsp&nbsp&nbsp&nbsp<input type="radio" name="newsletter[type]" value="email" checked="checked" />ایمیل<br/><br/>
گروه هایی که باید به آن ها خبر نامه ارسال شود  (اگر میخواهید به همه ارسال شود هیچ کدام را انتخاب نکنید):<br/>
{groups}<br/><br/>
شروع از : <input type="text" name="newsletter[start]" value="0" id="start" style="width:25px;">&nbsp;&nbsp;<font color="red" size="1">(برای ارسال به همه باید بر روی 0 باشد)</font><br/><br/>
تعداد ارسال در هر نوبت : <input type="text" name="newsletter[limit]" value="30" id="limit" style="width:25px;"><br/><br/>
فاصله زمانی بین هر نوبت ارسال : <input type="text" name="newsletter[time]" id="time"  value="3" style="width:25px;">&nbsp;&nbsp;<font color="red" size="1">(به دلیل اینکه به سرور فشار بیش از حد نیاید باید فاصله زمانی بین هر نوبت ارسال وجود داشته باشد)</font><br/><br/>
در حال حاظر <font color="green"><b>{members-1}</b></font> کاربر در خبرنامه اختصاصی اعضا عضو هستند و <font color="red"><b>{members-0}</b></font> کاربر مایل به دریافت خبرنامه نیستند.<br/>
<label><input name="newsletter[all]" type="checkbox" value="1" />&nbsp;ارسال خبرنامه برای تمامی کاربران سایت.</label>&nbsp;&nbsp;<font color="red" size="1">(بهتر است در موارد غیر ضروری این گزینه را فعال نکنید.)</font><br/><br/>
<input type="button" id="button" value="مرحله بعدی"/>&nbsp;<input type="reset" value="پاک کردن فرم" onclick="CKEDITOR.instances.textarea_newsletter_text.setData('')" />&nbsp;&nbsp;<font color="red" size="1">(در صورتی که تعداد اعضای خبرنامه زیاد باشد ارسال ایمیل ها چند دقیقه طول خواهد کشید.)</font>
</div>
</form>
<div id="end" style="display: none;">
<div id="progress" >
</div>
اطلاعات: <span id ="sent">0</span> از <span id ="htotal">0</span><br/>
وضعیت: <span id ="status">ارسال نشده</span><br/><hr>
<font color="red" size="4">توجه: به هیچ وجه تا پایان ارسال، این صفحه را نبندید</font>
</div>
<script language="JavaScript" type="text/javascript">
/*<![CDATA[*/
$('#button').click(function() {
			$('#button').attr("disabled", "disabled");
			apadana.value('textarea_newsletter_text', CKEDITOR.instances.textarea_newsletter_text.getData());
			$.ajax({
				type: 'POST',
				url: '{admin-page}&section=newsletter&do=total',
				data: apadana.serialize('newsletter-form'),
				dataType : 'json',
				beforeSend:function(){apadana.loading(1);},
				success: function(data)
				{
				    if (data.status == 'ok') {
					$('#total').val(data.total);
					$('#htotal').html(data.total);
					$( "#progress" ).progressbar({value: false});
					start = parseInt($('#start').val());
					limit = parseInt($('#limit').val());
					time = parseInt($('#time').val());
					total = parseInt($('#total').val());
					$('#status').html('<font color="green">در حال ارسال ...</font>');		
					apadana.hideID('startform');
					apadana.showID('end');
					apadana.html('newsletter-ajax', "");
					newsletter_send(start,time,limit,total);
				    }else{
					$('#button').removeAttr("disabled");
					apadana.fadeOut('newsletter-ajax', function(){
					    apadana.html('newsletter-ajax', data.msg);
					    apadana.fadeIn('newsletter-ajax');	
					 });
					apadana.scroll("#newsletter-ajax");
				    }
				},
				complete : function (){apadana.loading(0);}
			});
		});
function newsletter_send(start,time,limit,total)
{
	$.ajax({
		type: 'POST',
		url: '{admin-page}&section=newsletter&do=send',
		data: apadana.serialize('newsletter-form') ,
		dataType : 'json',
		success: function(data)
		{
		    if (data) {
			if (data.status == "finished") {
			    if (data.count >= total) {$('#status').html('<font color="orange">ارسال با موفقیت انجام شد</font>');}
			    else{$('#status').html('<font color="red">ارسال انجام شد ولی ارسال خبرنامه به بعضی از کاربران با مشکل مواجه شد!</font>');}
			}else if( data.status == "ok" ){
				start = data.count;
				prog = Math.round((start * 100) /total);
				if(prog > 100){prog = 100;}
				$('#sent').html(start);
				$('#start').val(start);
				$('#progress').progressbar( "option", "value", prog );
				if (data.count >= total){
				    $('#status').html('<font color="orange">ارسال با موفقیت انجام شد</font>');
				}else{
				    setTimeout(("newsletter_send("+start+","+time+","+limit+","+total+");"), time*1000 );
				}
			}
		    }
		}
	})
}
/*]]>*/
</script>