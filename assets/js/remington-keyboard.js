// ============================================
// REMINGTON MARATHI/HINDI KEYBOARD (CDAC/GIST)
// Clean final build for exams – normal & shift per chart
// ============================================

function initRemingtonTyping(language) {
  const typingArea = document.getElementById("typingArea");
  if (!typingArea) return;

  const resolvedLanguage = String(language || "").toLowerCase();
  const isSupported = resolvedLanguage === "marathi" || resolvedLanguage === "hindi";
  const allowBackspace = !!(window.typingOptions ? window.typingOptions.allowBackspace : true);

  if (!isSupported) return;

  // Devanagari-friendly font + ligatures to render conjuncts correctly
  typingArea.style.fontFamily = "'Mangal', 'Nirmala UI', 'Noto Sans Devanagari', sans-serif";
  typingArea.style.fontVariantLigatures = "normal";
  typingArea.style.fontFeatureSettings = '"liga" 1, "clig" 1, "locl" 1';

  // Blue = normal, Red = shift (matches supplied layout image)
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

    // Use plain reph marker (no dotted placeholder) so D+Z => "क्र" and Shift+V+Z => "ट्र".
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
    "!": "।",
    "@": "/",
    "#": ":",
    "$": "ऱ्",
    "%": "-",
    "^": "\"",
    "&": "\'",
    "*": "द्ध",
    "(": "त्र",
    ")": "ऋ",
    "_": "़",
    "+": "्",

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
    ":": "रू",
    "\"": "ष्",

    z: "र्",
    x: "ग्",
    c: "ब्",
    v: "ट",
    b: "ठ",
    n: "छ",
    m: "ड",
    "<": "ढ",
    ">": "झ",
    "?": "घ्"
  };

  const altMap = {}; // No AltGr layer in this layout

  typingArea.addEventListener("keydown", function (e) {
    if (e.ctrlKey || e.metaKey) return;

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
    const key =
      rawKey.length === 1 && rawKey >= "A" && rawKey <= "Z"
        ? rawKey.toLowerCase()
        : rawKey.length === 1
        ? rawKey
        : rawKey.toLowerCase();

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
    if (char !== "") insertText(this, char);
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
  const prev2 = text.slice(Math.max(0, start - 2), start);
  const consonantRE = /[कखगघङचछजझञटठडढणतथदधनपफबभमयरलवशषसहक़ख़ग़ज़ड़ढ़फ़य़]/;
  const charBeforeMatra = text[start - 2] || "";

  // Place the pre-base matra "ि" before the consonant visually but after in storage.
  // Only reorder if the "ि" is not already attached to a preceding consonant (prevents चि + क => चकि).
  const matraAlreadyAttached = charBeforeMatra && consonantRE.test(charBeforeMatra);
  if (prev === "ि" && char && char !== "्" && !matraAlreadyAttached) {
    const newText = text.slice(0, start - 1) + char + "ि" + text.slice(end);
    field.value = newText;
    field.selectionStart = field.selectionEnd = (start - 1) + char.length + 1;
    return;
  }

  // Normalize अ + ा => आ
  if (prev === "अ" && char === "ा") {
    field.value = text.slice(0, start - 1) + "आ" + text.slice(end);
    field.selectionStart = field.selectionEnd = start;
    return;
  }

  // Consonant + "्" + "ा" => add Ya before the long-AA matra (e.g., G + Shift= + K => "ह्या")
  if (prev === "्" && char === "ा" && consonantRE.test(charBeforeMatra)) {
    field.value = text.slice(0, start - 1) + "्या" + text.slice(end);
    field.selectionStart = field.selectionEnd = (start - 1) + "्या".length;
    return;
  }

  // र + ी followed by "र्" => reorder to "र्री" (J + H + Shift+Z)
  if (char === "र्" && prev === "ी" && charBeforeMatra === "र") {
    field.value = text.slice(0, start - 2) + "र्री" + text.slice(end);
    field.selectionStart = field.selectionEnd = (start - 2) + "र्री".length;
    return;
  }

  // ड + नुक्ता => ङ  (Shift+M then Shift+-)
  if (prev === "ड" && char === "़") {
    field.value = text.slice(0, start - 1) + "ङ" + text.slice(end);
    field.selectionStart = field.selectionEnd = start;
    return;
  }

  // Consonant + "र्" (Shift+Z) => prefix-half-ra before the consonant (e.g., E then Shift+Z => "र्म").
  if (char === "र्" && consonantRE.test(prev) && prev !== "्") {
    const newText = text.slice(0, start - 1) + char + prev + text.slice(end);
    field.value = newText;
    field.selectionStart = field.selectionEnd = (start - 1) + char.length + prev.length;
    return;
  }

  const combo = prev + char;

  // Common two-char conjuncts
  if (start > 0 && (combo === "ज्ञ" || combo === "त्र" || combo === "श्र" || combo === "क्ष" || combo === "द्य")) {
    const newText = text.slice(0, start - 1) + combo + text.slice(end);
    field.value = newText;
    field.selectionStart = field.selectionEnd = (start - 1) + combo.length;
    return;
  }

  // Retroflex/dental + र => consonant + halant + र  (ट्र, ठ्र, ड्र, ढ्र, त्र already handled)
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
// REMOVE PREVIOUS CHAR (used if needed elsewhere)
// =========================
function removePrev(field) {
  const pos = field.selectionStart;
  const text = field.value;
  if (pos === 0) return;
  field.value = text.slice(0, pos - 1) + text.slice(pos);
  field.selectionStart = field.selectionEnd = pos - 1;
}

// Auto-init if typingArea is present and global init not called manually
if (typeof window !== "undefined" && document.getElementById("typingArea")) {
  initRemingtonTyping((window.typingLanguage || "").toLowerCase());
}

