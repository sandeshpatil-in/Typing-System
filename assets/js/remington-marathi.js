// ================================
// REMINGTON MARATHI ENGINE
// ================================

function initRemingtonTyping(language) {
  const typingArea = document.getElementById("typingArea");
  if (!typingArea) return;

  const resolvedLanguage = String(language || "").toLowerCase();

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
    "-": "-",
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
    "\\": "ृ",

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
    if (resolvedLanguage !== "marathi") return;
    if (e.ctrlKey) return;

    if (e.key === "Backspace") {
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

  if (char === "्") {
    field.value = text.slice(0, start) + "्" + text.slice(end);
    field.selectionStart = field.selectionEnd = start + 1;
    return;
  }

  if (char === "ि" && start > 0) {
    field.value = text.slice(0, start - 1) + "ि" + prev + text.slice(end);
    field.selectionStart = field.selectionEnd = start + 1;
    return;
  }

  const combo = prev + char;

  if (combo === "ज्ञ" || combo === "त्र" || combo === "श्र" || combo === "क्ष") {
    removePrev(field);
    char = combo;
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
