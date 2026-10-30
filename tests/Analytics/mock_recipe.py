"""Run the existing mocked browser suite with owned, automatically stopped processes."""
import json
from pathlib import Path
import socket
import subprocess
import sys
import time
import urllib.request

sys.dont_write_bytecode = True
from real_driver import Driver

root = Path(__file__).resolve().parents[2]
for port in (8019, 9231):
    with socket.socket() as probe:
        probe.bind(('127.0.0.1', port))
output = root/'var/analytics-tests'/time.strftime('run-%Y%m%d-%H%M%S')
output.mkdir(parents=True)
php = chrome = None
code = 1
with (output/'process.log').open('w', encoding='utf-8') as log:
    try:
        php = subprocess.Popen(['php','-d','xdebug.mode=off','-S','127.0.0.1:8019','-t','public','tests/Analytics/preview.php'],
            cwd=root,stdout=log,stderr=log,creationflags=subprocess.CREATE_NO_WINDOW)
        chrome = subprocess.Popen(['C:/Program Files/Google/Chrome/Application/chrome.exe','--headless=new',
            '--no-first-run','--disable-background-networking','--disable-component-update','--disable-sync',
            '--host-resolver-rules=MAP * ~NOTFOUND, EXCLUDE 127.0.0.1, EXCLUDE localhost',
            '--remote-debugging-port=9231','--user-data-dir='+str(output/'chrome'),'about:blank'],
            stdout=log,stderr=log,creationflags=subprocess.CREATE_NO_WINDOW)
        for attempt in range(100):
            try:
                with urllib.request.urlopen('http://127.0.0.1:9231/json/version',timeout=1) as response:
                    version=json.load(response)
                break
            except OSError:
                time.sleep(.2)
        else:
            raise RuntimeError('Mock Chrome unavailable')
        code=subprocess.run([sys.executable,'tests/Analytics/browser.py',*sys.argv[1:]],cwd=root).returncode
    finally:
        if chrome:
            try:
                with urllib.request.urlopen('http://127.0.0.1:9231/json/version',timeout=1) as response:
                    driver=Driver(json.load(response)['webSocketDebuggerUrl'])
                driver.send('Browser.close'); driver.sock.close()
            except OSError:
                pass
        for process in (chrome,php):
            if process:
                try: process.wait(timeout=3)
                except subprocess.TimeoutExpired: process.terminate(); process.wait(timeout=5)
        print('Mock recipe processes stopped:',all(p is None or p.poll() is not None for p in (php,chrome)))
raise SystemExit(code)
