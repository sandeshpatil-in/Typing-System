// ================================
// REMINGTON MARATHI ENGINE
// ================================

function initRemingtonTyping(language) {
  const typingArea = document.getElementById("typingArea");
  if (!typingArea) return;

  const resolvedLanguage = String(language || "").toLowerCase();
  const isSupported = resolvedLanguage === "marathi" || resolvedLanguage === "hindi";
  const allowBackspace = !!(window.typingOptions ? window.typingOptions.allowBackspace : true);

  // Force a Devanagari-friendly font stack and enable ligatures so conjuncts like "द्य" render joined
  // even on systems that default to Latin-first stacks.
  if (isSupported) {
    typingArea.style.fontFamily = "'Mangal', 'Nirmala UI', 'Noto Sans Devanagari', sans-serif";
    typingArea.style.fontVariantLigatures = "normal";
    typingArea.style.fontFeatureSettings = '"liga" 1, "clig" 1, "locl" 1';
  }

  // CDAC/GIST Remington Marathi keyboard layout.
  // Blue characters in the keyboard chart = normalMap.
  // Red characters in the keyboard chart = shiftMap.
  const normalMap = {
    "`": "़",
    "1": "१",
    "2": "२",
    "3": "३",
    "4": "४",
    "5": "५",
    "6": "६",
    "7": "७",
    "8": "८",
    "9": "९",
    "0": "०",
    "-": "ञ",
    "=": "ृ",

    q: "ु",
    w: "ू",
    e: "म",
    r: "त",
    t: "ज",
    y: "ल",
    u: "न",
    i: "प",
    o: "व",
    p: "च",
    "[": "ख्",
    "]": ",",
    "\\": ".",

    a: "ं",
    s: "े",
    d: "क",
    f: "ि",
    g: "ह",
    h: "ी",
    j: "र",
    k: "ा",
    l: "स",
    ";": "य",
    "'": "श्",

    z: "्र",
    x: "ग",
    c: "ब",
    v: "अ",
    b: "इ",
    n: "द",
    m: "उ",
    ",": "ए",
    ".": "ण्",
    "/": "ध्"
  };

  const shiftMap = {
    "~": "द्य",
    "!": "ङ",
    "@": "/",
    "#": ":",
    "$": "ऱ्",
    "%": "-",
    "^": "\"",
    "&": "(",
    "*": ")",
    "(": "त्र",
    ")": "ऋ",
    "_": "'",
    "+": "द्",

    q: "फ",
    w: "ॅ",
    e: "म्",
    r: "त्",
    t: "ज्",
    y: "ल्",
    u: "न्",
    i: "प्",
    o: "व्",
    p: "च्",
    "{": "क्ष्",
    "}": "द्व",
    "|": "",

    a: "ा",
    s: "ै",
    d: "क्",
    f: "थ्",
    g: "ळ",
    h: "भ्",
    j: "श्र",
    k: "ज्ञ",
    l: "स्",
    ":": "य्",
    "\"": "ष्",

    z: "र्",
    x: "ग्",
    c: "ब्",
    v: "ट",
    b: "ठ",
    n: "छ",
    m: "ड",
    '<': "ढ",
    '>': "झ्",
    '?': "घ्"
  };

  const altMap = {};

  typingArea.addEventListener("keydown", function (e) {
    if (!isSupported) return;
    if (e.ctrlKey) return;

    if (e.key === "Backspace") {
      if (allowBackspace) return;

      e.preventDefault();

      const start = this.selectionStart;
      const text = this.value;

      if (start === 0) return;

      this.value = text.substring(0, start - 1) + text.substring(start);
      this.selectionStart = this.selectionEnd = start - 1;
      return;
    }

    const rawKey = e.key;
    const key = rawKey.length === 1 && rawKey >= "A" && rawKey <= "Z"
      ? rawKey.toLowerCase()
      : (rawKey.length === 1 ? rawKey : rawKey.toLowerCase());
    let char;

    if (e.altKey) {
      char = altMap[key];
    } else if (e.shiftKey) {
      char = shiftMap[key];
    } else {
      char = normalMap[key];
    }

    if (typeof char === "undefined") return;

    e.preventDefault();
    if (char !== "") {
      insertText(this, char);
    }
  });
}

// =========================
// INSERT LOGIC
// =========================
function insertText(field, char) {
  const start = field.selectionStart;
  const end = field.selectionEnd;
  const text = field.value;
  const prev = text[start - 1] || "";
  const prev2 = text.slice(Math.max(0, start - 2), start); // last two chars

  // If the previous key produced a standalone "ि" and the user now types a base letter,
  // move the matra after the base so the stored text stays in canonical order (consonant + ि).
  if (prev === "ि" && char && char !== "्") {
    const newText = text.slice(0, start - 1) + char + "ि" + text.slice(end);
    field.value = newText;
    field.selectionStart = field.selectionEnd = (start - 1) + char.length + 1;
    return;
  }

  // Normalize अ + ा to the single vowel आ so passages that use the atomic character match.
  if (prev === "अ" && char === "ा") {
    field.value = text.slice(0, start - 1) + "आ" + text.slice(end);
    field.selectionStart = field.selectionEnd = start;
    return;
  }

  const combo = prev + char;

  if (start > 0 && (combo === "ज्ञ" || combo === "त्र" || combo === "श्र" || combo === "क्ष" || combo === "द्य")) {
    const newText = text.slice(0, start - 1) + combo + text.slice(end);
    field.value = newText;
    field.selectionStart = field.selectionEnd = (start - 1) + combo.length;
    return;
  }

  // ट/ठ/ड/ढ + र => conjunct (e.g., Shift+V then Z => "ट्र")
  if (start > 0 && (prev === "ट" || prev === "ठ" || prev === "ड" || prev === "ढ") && char === "र") {
    const conj = prev + "्र";
    const newText = text.slice(0, start - 1) + conj + text.slice(end);
    field.value = newText;
    field.selectionStart = field.selectionEnd = (start - 1) + conj.length;
    return;
  }

  // त्र + य => त्र्य (used in त्र्यंबक etc.)
  if (prev2 === "त्र" && char === "य") {
    const newText = text.slice(0, start - 2) + "त्र्य" + text.slice(end);
    field.value = newText;
    field.selectionStart = field.selectionEnd = (start - 2) + "त्र्य".length;
    return;
  }

  field.value = text.slice(0, start) + char + text.slice(end);
  field.selectionStart = field.selectionEnd = start + char.length;
}

// =========================
// REMOVE PREVIOUS CHAR
// =========================
function removePrev(field) {
  const pos = field.selectionStart;
  const text = field.value;

  field.value = text.slice(0, pos - 1) + text.slice(pos);
  field.selectionStart = field.selectionEnd = pos - 1;
}
