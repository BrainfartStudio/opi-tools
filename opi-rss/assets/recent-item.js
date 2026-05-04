document.addEventListener("DOMContentLoaded", function () {
  const feedItems = document.querySelectorAll(".feed-item .feed-source a");
  feedItems.forEach((link) => {
    try {
      const url = new URL(link.href);
      link.href = url.origin + '/';
      link.textContent = link.textContent.trim();
    } catch (e) {
      // Ignore invalid URLs
    }
  });
});