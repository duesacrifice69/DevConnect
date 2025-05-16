const imageUploader = document.querySelector(".image-uploader");

if (imageUploader) {
  const hiddenFileInput = document.createElement("input");
  const img = document.createElement("img");
  const defaultImageSrc = imageUploader.dataset.src
    ? imageUploader.dataset.src
    : "assets/images/image-upload.png";
  img.src = defaultImageSrc;
  img.alt = "Uploaded Image";
  img.addEventListener("click", (event) => {
    hiddenFileInput.click();
  });

  imageUploader.appendChild(img);
  hiddenFileInput.id = imageUploader.dataset.id;
  hiddenFileInput.name = imageUploader.dataset.name;
  hiddenFileInput.required = imageUploader.dataset.required == "true";
  hiddenFileInput.type = "file";
  hiddenFileInput.accept = "image/*";
  hiddenFileInput.addEventListener("change", (event) => {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });

  imageUploader.appendChild(hiddenFileInput);
}
