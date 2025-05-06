<!-- BEGIN: main -->
<h1>{FILE_NAME}</h1>
<!-- BEGIN: img -->
<div>
  <img width="100%" src="{FILE_PATH}" />
</div>
<!-- END: img -->
<!-- BEGIN: video -->
<div>
  <video width="100%" controls>
    <source src="{FILE_PATH}" type="video/mp4" />
    Your browser does not support the video tag.
  </video>
</div>
<!-- END: video -->
<!-- BEGIN: audio -->
<div>
  <audio controls>
    <source src="{FILE_PATH}" type="audio/mpeg" />
    Your browser does not support the audio element.
  </audio>
</div>
<!-- END: audio -->
 <!-- BEGIN: powerpoint -->
<div>
  <p class="text-center">{LANG.error_reading_ppt}</p>
</div>
<div class="alert alert-warning text-center">{LANG.download_to_view}</div>
<!-- END: powerpoint -->
<!-- END: main -->