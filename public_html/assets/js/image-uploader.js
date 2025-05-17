const imageUploader = document.querySelector(".image-uploader");

if (imageUploader) {
  const hiddenFileInput = document.createElement("input");
  const img = new Image();
  const defaultImageSrc = imageUploader.dataset.src
    ? imageUploader.dataset.src
    : "assets/images/image-upload.png";
  img.src = defaultImageSrc;
  img.alt = "Uploaded Image";

  imageUploader.appendChild(img);
  hiddenFileInput.id = imageUploader.dataset.id;
  hiddenFileInput.name = imageUploader.dataset.name;
  hiddenFileInput.required = imageUploader.dataset.required == "true";
  hiddenFileInput.type = "file";
  hiddenFileInput.accept = "image/*";

  imageUploader.appendChild(hiddenFileInput);

  img.addEventListener("click", (event) => {
    hiddenFileInput.click();
  });
  img.addEventListener("dragenter", (event) => {
    event.stopPropagation();
    event.preventDefault();
    imageUploader.classList.add("dragover");
  });
  img.addEventListener("dragover", (event) => {
    event.stopPropagation();
    event.preventDefault();
  });
  img.addEventListener("drop", (event) => {
    event.stopPropagation();
    event.preventDefault();
    const dt = event.dataTransfer;
    const files = dt.files;
    if (files.length > 0) {
      hiddenFileInput.files = files;
    }
    handleFiles(files);
    imageUploader.classList.remove("dragover");
    console.log("Files dropped:", hiddenFileInput.files);
    
  });
  img.addEventListener("dragleave", (event) => {
    event.stopPropagation();
    event.preventDefault();
    imageUploader.classList.remove("dragover");
  });

  hiddenFileInput.addEventListener("change", (event) => {
    handleFiles(event.target.files);
  });

  function handleFiles(files) {
    const file = files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  }
}
