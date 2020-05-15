!function(e){"object"==typeof exports&&"object"==typeof module?e(require("../../lib/codemirror")):"function"==typeof define&&define.amd?define(["../../lib/codemirror"],e):e(CodeMirror)}(function(R){"use strict";R.defineMode("vb",function(r,c){var d="error";function e(e){return new RegExp("^(("+e.join(")|(")+"))\\b","i")}var u=new RegExp("^[\\+\\-\\*/%&\\\\|\\^~<>!]"),s=new RegExp("^[\\(\\)\\[\\]\\{\\}@,:`=;\\.]"),l=new RegExp("^((==)|(<>)|(<=)|(>=)|(<>)|(<<)|(>>)|(//)|(\\*\\*))"),m=new RegExp("^((\\+=)|(\\-=)|(\\*=)|(%=)|(/=)|(&=)|(\\|=)|(\\^=))"),f=new RegExp("^((//=)|(>>=)|(<<=)|(\\*\\*=))"),h=new RegExp("^[_A-Za-z][_A-Za-z0-9]*"),t=["class","module","sub","enum","select","while","if","function","get","set","property","try","structure","synclock","using","with"],n=["else","elseif","case","catch","finally"],i=["next","loop"],o=["and","andalso","or","orelse","xor","in","not","is","isnot","like"],p=e(o),a=["#const","#else","#elseif","#end","#if","#region","addhandler","addressof","alias","as","byref","byval","cbool","cbyte","cchar","cdate","cdbl","cdec","cint","clng","cobj","compare","const","continue","csbyte","cshort","csng","cstr","cuint","culng","cushort","declare","default","delegate","dim","directcast","each","erase","error","event","exit","explicit","false","for","friend","gettype","goto","handles","implements","imports","infer","inherits","interface","isfalse","istrue","lib","me","mod","mustinherit","mustoverride","my","mybase","myclass","namespace","narrowing","new","nothing","notinheritable","notoverridable","of","off","on","operator","option","optional","out","overloads","overridable","overrides","paramarray","partial","private","protected","public","raiseevent","readonly","redim","removehandler","resume","return","shadows","shared","static","step","stop","strict","then","throw","to","true","trycast","typeof","until","until","when","widening","withevents","writeonly"],b=["object","boolean","char","string","byte","sbyte","short","ushort","int16","uint16","integer","uinteger","int32","uint32","long","ulong","int64","uint64","decimal","single","double","float","date","datetime","intptr","uintptr"],g=e(a),y=e(b),v='"',w=e(t),x=e(n),k=e(i),I=e(["end"]),E=e(["do"]);function L(e,t){t.currentIndent++}function z(e,t){t.currentIndent--}function C(e,t){if(e.eatSpace())return null;var n,r,i;if("'"===e.peek())return e.skipToEnd(),"comment";if(e.match(/^((&H)|(&O))?[0-9\.a-f]/i,!1)){var o=!1;if((e.match(/^\d*\.\d+F?/i)||e.match(/^\d+\.\d*F?/)||e.match(/^\.\d+F?/))&&(o=!0),o)return e.eat(/J/i),"number";var a=!1;if(e.match(/^&H[0-9a-f]+/i)||e.match(/^&O[0-7]+/i)?a=!0:e.match(/^[1-9]\d*F?/)?(e.eat(/J/i),a=!0):e.match(/^0(?![\dx])/i)&&(a=!0),a)return e.eat(/L/i),"number"}return e.match(v)?(t.tokenize=(n=e.current(),r=1==n.length,i="string",function(e,t){for(;!e.eol();){if(e.eatWhile(/[^'"]/),e.match(n))return t.tokenize=C,i;e.eat(/['"]/)}if(r){if(c.singleLineStringErrors)return d;t.tokenize=C}return i}),t.tokenize(e,t)):e.match(f)||e.match(m)?null:e.match(l)||e.match(u)||e.match(p)?"operator":e.match(s)?null:e.match(E)?(L(0,t),t.doInCurrentLine=!0,"keyword"):e.match(w)?(t.doInCurrentLine?t.doInCurrentLine=!1:L(0,t),"keyword"):e.match(x)?"keyword":e.match(I)?(z(0,t),z(0,t),"keyword"):e.match(k)?(z(0,t),"keyword"):e.match(y)||e.match(g)?"keyword":e.match(h)?"variable":(e.next(),d)}return R.registerHelper("hintWords","vb",t.concat(n).concat(i).concat(o).concat(a).concat(b)),{electricChars:"dDpPtTfFeE ",startState:function(){return{tokenize:C,lastToken:null,currentIndent:0,nextLineIndent:0,doInCurrentLine:!1}},token:function(e,t){e.sol()&&(t.currentIndent+=t.nextLineIndent,t.nextLineIndent=0,t.doInCurrentLine=0);var n=function(e,t){var n=t.tokenize(e,t),r=e.current();if("."===r)return"variable"===(n=t.tokenize(e,t))?"variable":d;var i="[({".indexOf(r);return-1!==i&&L(0,t),-1!==(i="])}".indexOf(r))&&z(0,t)?d:n}(e,t);return t.lastToken={style:n,content:e.current()},n},indent:function(e,t){var n=t.replace(/^\s+|\s+$/g,"");return n.match(k)||n.match(I)||n.match(x)?r.indentUnit*(e.currentIndent-1):e.currentIndent<0?0:e.currentIndent*r.indentUnit},lineComment:"'"}}),R.defineMIME("text/x-vb","vb")});