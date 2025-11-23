// Hero slideshow with clickable dots
const slides = document.getElementById('slides');
const dotsContainer = document.getElementById('dots');
let idx = 0;

// Create dots dynamically
for (let i = 0; i < slides.children.length; i++) {
  const dot = document.createElement('div');
  dot.className = 'dot';
  if (i === 0) dot.classList.add('active');
  dot.addEventListener('click', () => { idx = i; updateHero(); });
  dotsContainer.appendChild(dot);
}

function updateHero() {
  slides.style.transform = `translateX(-${idx * 100}%)`;
  Array.from(dotsContainer.children).forEach((dot, i) => {
    dot.classList.toggle('active', i === idx);
  });
}

// Auto-slide every 4 seconds
setInterval(() => {
  idx = (idx + 1) % slides.children.length;
  updateHero();
}, 4000);
