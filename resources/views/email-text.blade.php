{{ $subjectLine }}
{{ str_repeat('=', strlen($subjectLine)) }}

@if (trim($summaryText) !== '')
{{ $summaryText }}
@endif
@if (trim($summaryText) !== '' && trim($emailContent) !== '')

@endif
@if (trim($emailContent) !== '')
{{ $emailContent }}
@endif
