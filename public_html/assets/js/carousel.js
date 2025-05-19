let currentIndex = 0;
let autoplay;

const slides = document.querySelectorAll(".carousel-item");
const totalSlides = slides.length;
const carouselContainer = document.querySelector(".carousel-container");
const carousel = document.querySelector(".carousel");

// Clone first and last slides for infinite effect
const firstClone = slides[0].cloneNode(true);
const lastClone = slides[totalSlides - 1].cloneNode(true);
carouselContainer.appendChild(firstClone);
carouselContainer.insertBefore(lastClone, slides[0]);

// Dots for position indicators
const dotsContainer = document.createElement("div");
dotsContainer.classList.add("dots");
carousel.appendChild(dotsContainer);
for (let i = 0; i < totalSlides; i++) {
  const dot = document.createElement("div");
  dot.classList.add("dot");
  dotsContainer.appendChild(dot);
}
const dots = document.querySelectorAll(".dot");

// Update carousel width and initial position
const allSlides = document.querySelectorAll(".carousel-item");
carouselContainer.style.width = `${allSlides.length * 100}%`;
updateCarousel();

function moveSlide() {
  currentIndex++;
  updateCarousel();

  // Handle infinite loop
  if (currentIndex >= totalSlides) {
    setTimeout(() => {
      carouselContainer.style.transition = "none";
      currentIndex = 0;

      updateCarousel();
      setTimeout(() => {
        carouselContainer.style.transition = "transform 0.5s ease";
      }, 50);
    }, 500);
  } else if (currentIndex < 0) {
    setTimeout(() => {
      carousel.style.transition = "none";

      currentIndex = totalSlides - 1;
      updateCarousel();
      setTimeout(() => {
        carouselContainer.style.transition = "transform 0.5s ease";
      }, 50);
    }, 500);
  }
}

// Update carousel position
function updateCarousel() {
  dots.forEach((dot, i) =>
    i === currentIndex
      ? dot.classList.add("active")
      : dot.classList.remove("active")
  );
  carouselContainer.style.transform = `translateX(-${
    ((currentIndex + 1) * 100) / allSlides.length
  }%)`;
}

autoplay = setInterval(() => moveSlide(), 5000);
carousel.addEventListener("mouseover", () => {
  clearInterval(autoplay);
});

carousel.addEventListener("mouseout", () => {
  autoplay = setInterval(() => moveSlide(), 5000);
});
