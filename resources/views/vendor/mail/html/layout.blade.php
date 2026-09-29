{{-- Legacy's own, ported as it is: ../viak.ch/resources/views/vendor/mail/html/layout.blade.php ([[10-mail]]). --}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
@import url("https://use.typekit.net/bmx5jih.css");

@media only screen and (max-width: 660px) {

  body {
    font-size: 16px !important; 
  }

  p {
    font-size: 16px !important; 
    margin: 0 0 16px 0 !important;
  }

  .wrapper {
    padding: 0 8px !important;
  }

  .header {
    padding-bottom: 16px !important;
  }
  
  .content-inner {
    padding: 24px 0 !important;
  }

  .inner-body {
    width: 100% !important;
  }

  .content-cell {
    padding: 16px !important;
  }

  .content-table td:first-of-type {
    width: 80px !important;
  }

  h1 {
    font-size: 18px !important;
  }
}
</style>
<!--[if mso]>
<style>
  .content-table td {
    padding: 0 !important;
  }
  .content-table td:first-of-type {
    width: auto !important;
  }
</style>
<![endif]-->
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0">
<table class="inner-body" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{{ $header ?? '' }}
<div class="content-inner">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</div>
{{ $footer ?? '' }}
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
