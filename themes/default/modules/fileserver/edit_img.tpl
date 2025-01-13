<!-- BEGIN: main -->
<h1>{FILE_NAME}</h1>
<!-- BEGIN: img -->
<div>
   <img width="100%" src="{FILE_PATH}">
</div>
<!-- END: img -->
<!-- BEGIN: video -->
<div>
   <video width="320" height="240" controls>
      <source src="{FILE_PATH}" type="video/mp4">
    Your browser does not support the video tag.
    </video>
</div>
<!-- END: video -->
 <!-- BEGIN: audio -->
<div>
   <audio controls>
      <source src="{FILE_PATH}" type="audio/mpeg">
    Your browser does not support the audio element.
    </audio>
</div>
<!-- END: audio -->
<!-- END: main -->