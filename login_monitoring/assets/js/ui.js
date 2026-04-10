function switchMode(mode) {
    document.querySelectorAll('.card').forEach(c=>{
        c.classList.remove('show');
    });

    document.querySelectorAll('.tab').forEach(t=>{
        t.classList.remove('active');
    });

    if(mode === 'A') {
        document.getElementById('modeA').classList.add('show');
        document.querySelectorAll('.tab')[0].classList.add('active');
    } else {
        document.getElementById('modeB').classList.add('show');
        document.querySelectorAll('.tab')[1].classList.add('active');
    }
}
