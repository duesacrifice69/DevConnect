function showConfirm(text, callback) {
  const popup = document.createElement("div");
  popup.style.display = "flex";
  popup.classList.add("popup");
  popup.innerHTML = `
        <form>
            <h2>Confirmation !</h2>
            <p>${text}</p>
            <div style="display: flex; justify-content: space-evenly;margin-top: 24px;">
                <button type="button" class="button" id="cancel">Cancel</button>
                <button type="button" class="button" id="confirm">Confirm</button>
            </div>
        </form>
    `;

  document.body.appendChild(popup);
  scrollLock(true);

  popup.querySelector("#confirm").addEventListener("click", () => {
    popup.children[0].classList.add("closed");
    scrollLock(false);
    setTimeout(() => {
      document.body.removeChild(popup);
    }, 200);
    callback(true);
  });
  popup.querySelector("#cancel").addEventListener("click", () => {
    popup.children[0].classList.add("closed");
    scrollLock(false);
    setTimeout(() => {
      document.body.removeChild(popup);
    }, 200);
    callback(false);
  });
}

function scrollLock(enable) {
  if (enable) {
    document.querySelector("body").style.overflow = "hidden";
    if (isVerticalScrollbarEnabled()) {
      document.querySelector(
        "body"
      ).style.paddingRight = `${getScrollBarWidth()}px`;
    }
  } else {
    document.querySelector("body").removeAttribute("style");
  }
}

function handleOpenPopup() {
  document.querySelector(".popup>form").classList.remove("closed");
  document.querySelector(".popup").style.display = "flex";
  scrollLock(true);
}

function handleClosePopup() {
  document.querySelector(".popup>form").classList.add("closed");
  scrollLock(false);
  setTimeout(() => {
    document.querySelector(".popup").style.display = "none";
  }, 200);
}

function getScrollBarWidth() {
  const outer = document.createElement("div");
  outer.style.visibility = "hidden";
  outer.style.width = "100px";
  outer.style.position = "absolute";
  outer.style.top = "-9999px";
  document.body.appendChild(outer);

  const widthNoScroll = outer.offsetWidth;

  outer.style.overflow = "scroll";

  const inner = document.createElement("div");
  inner.style.width = "100%";
  outer.appendChild(inner);

  const widthWithScroll = outer.clientWidth;

  document.body.removeChild(outer);

  return widthNoScroll - widthWithScroll;
}

function isVerticalScrollbarEnabled() {
  return document.body.scrollHeight > window.innerHeight;
}
