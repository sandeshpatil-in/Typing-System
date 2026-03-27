// ================================
// REMINGTON MARATHI ENGINE (FULL)
// ================================

function initRemingtonTyping(language){

  const typingArea = document.getElementById("typingArea");
  if(!typingArea) return;

// NORMAL KEYS
const normalMap = {
    'q': 'ु',
    'w': 'ू',
    'e': 'म', 
    'r': 'त', 
    't': 'ज', 
    'y': 'ल', 
    'u': 'न', 
    'i': 'प', 
    'o': 'व', 
    'p': 'च',
    '[': 'क्ष',
    ']': 'द्व',

    'a': 'ो', 
    's': 'े', 
    'd': 'क', 
    'f': 'ि', 
    'g': 'ह', 
    'h': 'ी', 
    'j': 'र', 
    'k': 'ा', 
    'l': 'स', 
    ';': 'य',

    'z': 'े', 
    'x': 'ग', 
    'c': 'ब', 
    'v': 'अ', 
    'b': 'इ', 
    'n': 'द', 
    'm': 'उ', 
    ',': 'ए', 
    '.': '', 
    

    '1': '१', 
    '2': '२', 
    '3': '३', 
    '4': '४', 
    '5': '५', 
    '6': '६', 
    '7': '७', 
    '8': '८', 
    '9': '९', 
    '0': '०',
    '-': '-', 
    '=': 'ृ'
};


// Shift ya Alt keys (Upar wali red/blue keys)
const shiftMap = {

    'q': 'फ',
    'w': 'ॅ', 
    'e': '्र', 
    'r': 'र्', 
    't': 'ज्ञ', 
    'y': 'ळ', 
    'u': 'न्', 
    'i': 'प', 
    'o': 'व्', 
    'p': 'च्',


    'a': 'ओ', 
    's': 'ए', 
    'd': 'क्', 
    'f': 'इ', 
    'g': 'ः', 
    'h': 'ई', 
    'j': 'श्र', 
    'k': 'आ', 
    'l': 'स्', 
    ';': 'य़',

    'z': 'ऐ', 
    'x': 'ग्', 
    'c': 'ब्', 
    'v': 'ट', 
    'b': 'ठ', 
    'n': 'छ', 
    'm': 'ड', 
    ',': 'ढ', 
    '.': 'झ', 
    '/': 'घ',
    '[': 'क्ष', 
    ']': 'द्व', 
    
    '`': 'द्य',
    '1': '!', 
    '2': '/', 
    '3': ':', 
    '4': '=', 
    '5': '-', 
    '6': '"', 
    '7': '\'', 
    '8': 'द्ध', 
    '9': 'त्र', 
    '0': 'ऋ',
    '-': '.', 
    '=': 'ञ'
};

  // =========================
  // KEYBOARD EVENT
  // =========================
  typingArea.addEventListener("keydown", function(e){

    if(language !== "marathi") return;

    if(e.ctrlKey) return;

    let key = e.key.toLowerCase();
    let char = '';

    // BACKSPACE
    if(e.key === "Backspace"){
      e.preventDefault();

      let start = this.selectionStart;
      let text = this.value;

      if(start === 0) return;

      this.value = text.substring(0, start - 1) + text.substring(start);
      this.selectionStart = this.selectionEnd = start - 1;
      return;
    }

    // ALT (AltGr)
    if(e.altKey){
      char = altMap[key];
    }
    // SHIFT
    else if(e.shiftKey){
      char = shiftMap[key];
    }
    // NORMAL
    else{
      char = normalMap[key];
    }

    if(!char) return;

    e.preventDefault();
    insertText(this, char);
  });

}


// =========================
// INSERT LOGIC
// =========================
function insertText(field, char){

  let start = field.selectionStart;
  let end = field.selectionEnd;
  let text = field.value;
  let prev = text[start - 1] || '';

  // HALF CHAR
  if(char === '्'){
    field.value = text.slice(0, start) + '्' + text.slice(end);
    field.selectionStart = field.selectionEnd = start + 1;
    return;
  }

  // MATRA (ि BEFORE)
  if(char === 'ि'){
    if(start > 0){
      field.value =
        text.slice(0, start - 1) +
        'ि' + prev +
        text.slice(end);

      field.selectionStart = field.selectionEnd = start + 1;
      return;
    }
  }

  // COMPLEX LIGATURES
  let combo = prev + char;

  if(combo === 'ज्ञ'){
    removePrev(field);
    char = 'ज्ञ';
  }
  if(combo === 'त्र'){
    removePrev(field);
    char = 'त्र';
  }
  if(combo === 'श्र'){
    removePrev(field);
    char = 'श्र';
  }

  // NORMAL INSERT
  field.value =
    text.slice(0, start) +
    char +
    text.slice(end);

  field.selectionStart = field.selectionEnd = start + char.length;
}


// =========================
// REMOVE PREVIOUS CHAR
// =========================
function removePrev(field){
  let pos = field.selectionStart;
  let text = field.value;

  field.value =
    text.slice(0, pos - 1) +
    text.slice(pos);

  field.selectionStart = field.selectionEnd = pos - 1;
}