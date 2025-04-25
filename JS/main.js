  //bubble style
  
  for (let i = 0; i < 25; i++) {
    let bubble = document.createElement("div");
    bubble.classList.add("bubble");
    let size = Math.random() * 40 + 10; // 10px to 50px
    bubble.style.width = `${size}px`;
    bubble.style.height = `${size}px`;
    bubble.style.left = `${Math.random() * 100}%`;
    bubble.style.animationDuration = `${Math.random() * 10 + 5}s`;
    bubble.style.animationDelay = `${Math.random() * 5}s`;
    document.body.appendChild(bubble);
  }

