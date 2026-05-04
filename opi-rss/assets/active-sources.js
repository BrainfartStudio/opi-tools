document.addEventListener("DOMContentLoaded", function () {
  const sourcesContainer = document.querySelector('[data-wpra-template="sources"]');
  if (!sourcesContainer) return;
  const listItems = Array.from(sourcesContainer.querySelectorAll(".wpra-item"));
  const cleanedItems = listItems
    .map((item) => {
      const source = item.querySelector(".feed-source a");
      if (!source) return null;
      const url = new URL(source.href);
      source.href = url.origin + '/';
      source.textContent = source.textContent.trim();
      const li = document.createElement("li");
      li.className = "wpra-item";
      li.appendChild(source.cloneNode(true));
      return li;
    })
    .filter(Boolean);
  cleanedItems.sort((a, b) => {
    const textA = a.textContent.trim().toLowerCase();
    const textB = b.textContent.trim().toLowerCase();
    return textA.localeCompare(textB);
  });
  const listParent = sourcesContainer.querySelector("ul");
  if (listParent) {
    listParent.innerHTML = "";
    cleanedItems.forEach((li) => listParent.appendChild(li));
  }
});