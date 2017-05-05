<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" class="minesweeper" style="background-color:#f2f2f2; height:100%; width:100%; border-radius:0px 0px 5px 5px; overflow:hidden;">
<?php
/*Console*/
//Подключаем библиотеки

//Инициализируем переменные
$click=$_GET['mobile'];
$appdownload=$_GET['appdownload'];
$type=$_GET['type'];
$folder=$_GET['destination'];
$text=preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))',$_GET["command"]);
//Логика
?>
<link rel="stylesheet" href="<?echo $folder;?>css/style.css">

<table>
    <tr>
        <td colspan="7">
            <input type="text" id="display" value="" />
        </td>
    </tr>
    <tr>
        <td>
            <input id="btnAns" type="button" name="operator" value="Ans" />
        </td>
        <td>
            <input id="btnPi" type="button" name="operator" value="π" onclick="set('3.14')" />
        </td>
        <td>
            <input id="btnE" type="button" name="operator" value="e" />
        </td>
        <td>
            <input id="btnOParen" type="button" name="operator" value="(" onclick="set('(')" />
        </td>
        <td>
            <input id="btnCParen" type="button" name="operator" value=")" onclick="set(')')" />
        </td>
        <td>
            <input id="btnPcnt" type="button" name="operator" value="%" />
        </td>
        <td>
            <input id="btnCE" type="button" name="operator" value="CE" onclick="ce()" />
        </td>
    </tr>
    <tr>
        <td>
            <input id="btnRad" type="button" name="operator" value="rad" />
        </td>
        <td>
            <input id="btnDeg" type="button" name="operator" value="deg" />
        </td>
        <td>
            <input id="btnFact" type="button" name="operator" value="x!" />
        </td>
        <td>
            <input id="btn7" type="button" value="7" onclick="set('7')" />
        </td>
        <td>
            <input id="btn8" type="button" value="8" onclick="set('8')" />
        </td>
        <td>
            <input id="btn9" type="button" value="9" onclick="set('9')" />
        </td>
        <td>
            <input id="btnDiv" type="button" name="operator" value="÷" onclick="set('/')" />
        </td>
    </tr>
    <tr>
        <td>
            <input id="btnSineInv" type="button" name="operator" value="asin" onclick="asine()" />
        </td>
        <td>
            <input id="btnSine" type="button" name="operator" value="sin" onclick="sine()" />
        </td>
        <td>
            <input id="btnLN" type="button" name="operator" value="ln" />
        </td>
        <td>
            <input id="btn4" type="button" value="4" onclick="set('4')" />
        </td>
        <td>
            <input id="btn5" type="button" value="5" onclick="set('5')" />
        </td>
        <td>
            <input id="btn6" type="button" value="6" onclick="set('6')" />
        </td>
        <td>
            <input id="btnMul" type="button" name="operator" value="×" onclick="set('*')" />
        </td>
    </tr>
    <tr>
        <td>
            <input id="btnCosInv" type="button" name="operator" value="acos" onclick="acosine()" />
        </td>
        <td>
            <input id="btnCos" type="button" name="operator" value="cos" onclick="cosine()" />
        </td>
        <td>
            <input id="btnLog" type="button" name="operator" value="log" onclick="fLog()" />
        </td>
        <td>
            <input id="btn1" type="button" value="1" onclick="set('1')" />
        </td>
        <td>
            <input id="btn2" type="button" value="2" onclick="set('2')" />
        </td>
        <td>
            <input id="btn3" type="button" value="3" onclick="set('3')" />
        </td>
        <td>
            <input id="btnSub" type="button" name="operator" value="-" onclick="set('-')" />
        </td>
    </tr>
    <tr>
        <td>
            <input id="btnTanInv" type="button" name="operator" value="atan" onclick="atangent()" />
        </td>
        <td>
            <input id="btnTan" type="button" name="operator" value="tan" onclick="tangent()" />
        </td>
        <td>
            <input id="btnSqrt" type="button" name="operator" value="√" onclick="sqrRoot()" />
        </td>
        <td>
            <input id="btn0" type="button" value="0" onclick="set('0')" />
        </td>
        <td>
            <input id="btnPeriod" type="button" value="." />
        </td>
        <td>
            <input id="btnEqual" type="button" name="equal" value="=" onclick="answer()" />
        </td>
        <td>
            <input id="btnAdd" type="button" name="operator" value="+" onclick="set('+')" />
        </td>
</table>
<script>
function set(op) {

    document.getElementById("display").value += op;

}

function sqrRoot() {
    var tempStore = document.getElementById("display").value;
    document.getElementById("display").value = eval(Math.sqrt(tempStore));

}

function asine() {
    var tempStore = document.getElementById("display").value;
    document.getElementById("display").value = eval(Math.asin(tempStore));

}

function acosine() {
    var tempStore = document.getElementById("display").value;
    document.getElementById("display").value = eval(Math.acos(tempStore));

}

function fLog() {
    var tempStore = document.getElementById("display").value;
    document.getElementById("display").value = eval(Math.log(tempStore));

}

function atangent() {
    var tempStore = document.getElementById("display").value;
    document.getElementById("display").value = eval(Math.atan(tempStore));

}

function tangent() {
    var tempStore = document.getElementById("display").value;
    document.getElementById("display").value = eval(Math.tan(tempStore));

}

function cosine() {
    var tempStore = document.getElementById("display").value;
    document.getElementById("display").value = eval(Math.cos(tempStore));

}

function sine() {
    var tempStore = document.getElementById("display").value;
    document.getElementById("display").value = eval(Math.sin(tempStore));

}

function setOp() {
    alert("gf");
    //document.getElementById("display").value += op;
}

function answer() {
    var Exp = document.getElementById("display");
    var Exp1 = Exp.value;
    var result = eval(Exp1);
    //alert(result);
    Exp.value = result;
}

function ce() {

    var elem = document.getElementById("display").value;
    var length = elem.length;
    length--;
    var a = elem.substr(0, length);

    // document.getElementById("display").value="";
    //for(var i=0;i<length-1;i++)
    //{
    document.getElementById("display").value = a;
    // }
    //alert(length);
}
</script>
</div>
