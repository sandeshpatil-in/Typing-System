function initRemingtonTyping(language){

  const typingArea = document.getElementById("typingArea");
  if(!typingArea) return;

  // =========================
  // NORMAL KEYS (OFFICIAL STYLE)
  // =========================
  const normalMap = {

    // TOP ROW (numbers)
    "`": "द्य",
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
    "=": " ",

    // Second ROW

    "q": "ु",
    "w": "ू",
    "e": "म",
    "r": "त",
    "t": "ज",
    "y": "ल",
    "u": "न",
    "i": "प",
    "o": "व",
    "p": "च",
    "[": "",
    "]": ",",

};


  // =========================
  // SHIFT KEYS
  // =========================
  const shiftMap = {

    // TOP ROW SHIFT
    '1':'!','2':'@','3':'#','4':'₹','5':'%',
    '6':'^','7':'&','8':'*','9':'(','0':')',

    '-':'_','=':'+',

    // Q ROW SHIFT
    'q':'औ','w':'ऐ','e':'आ','r':'ई','t':'ऊ',
    'y':'भ','u':'ङ','i':'घ','o':'ध','p':'झ',
    '[':'ढ',']':'ञ',

    // A ROW SHIFT
    'a':'ओ','s':'ए','d':'अ','f':'इ','g':'उ',
    'h':'फ','j':'ऱ','k':'ख','l':'थ',';':'छ',
    "'":'ठ',

    // Z ROW SHIFT
    'z':'ँ','x':'ण','c':'न्','v':'ट','b':'ळ',
    'n':'श','m':'ष',',':'क्ष','.':'त्र','/':'ज्ञ'
  };

  // =========================
  // ALT (AltGr) KEYS
  // =========================
  const altMap = {
    'q':'ॄ','w':'ॢ','e':'ॣ','r':'ऱ',
    'a':'ॉ','s':'ॅ','d':'ॐ'
  };

  // =========================
  // KEY EVENT
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

    // ALT
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

  // HALF CHAR (्)
  if(char === '्'){
    field.value = text.slice(0, start) + '्' + text.slice(end);
    field.selectionStart = field.selectionEnd = start + 1;
    return;
  }

  // MATRA 'ि' (goes before)
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

  // COMMON LIGATURES
  let combo = prev + char;

  if(combo === 'ज्ञ' || combo === 'त्र' || combo === 'श्र' || combo === 'क्ष'){
    removePrev(field);
    char = combo;
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