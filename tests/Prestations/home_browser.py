"""Homepage checks against home_preview.php, with synthetic data and no external network.

php -d xdebug.mode=off -S 127.0.0.1:8018 -t public tests/Prestations/home_preview.php
Start an isolated Chrome with --remote-debugging-port=9231, then run this file.
"""
import base64
import json
from pathlib import Path
import sys

sys.dont_write_bytecode = True
import browser as driver

driver.ORIGIN = 'http://127.0.0.1:8018'
artifacts = Path(__file__).resolve().parents[2] / 'var/prestations-tests/home-captures'
artifacts.mkdir(parents=True, exist_ok=True)
browser = driver.Browser(allowed_origin=driver.ORIGIN)
browser.call('Network.enable')
browser.call('Network.setBlockedURLs', {'urls': ['*googleapis.com*', '*google.com*', '*gstatic.com*', '*stripe*', '*francetuncq.fr*']})
browser.call('Fetch.enable', {'patterns': [{'urlPattern': '*', 'requestStage': 'Request'}]})


def key(name, code):
    for event in ('keyDown', 'keyUp'):
        browser.call('Input.dispatchKeyEvent', {'type': event, 'key': name, 'code': name, 'windowsVirtualKeyCode': code})


def capture(name):
    browser.evaluate("document.querySelector('#appointment').scrollIntoView({block: 'center', behavior: 'instant'})")
    browser.wait("document.querySelector('#appointment img').naturalWidth > 0")
    box = browser.evaluate("""(() => {const r = document.querySelector('#appointment').getBoundingClientRect();
        return {x:r.x + scrollX, y:r.y + scrollY, width:r.width, height:r.height, scale:1};})()""")
    result = browser.call('Page.captureScreenshot', {'format': 'png', 'captureBeyondViewport': True, 'clip': box})
    (artifacts / name).write_bytes(base64.b64decode(result['data']))


checks = []
for width, height, mobile in [(1365, 900, False), (390, 844, True)]:
    label = 'mobile' if mobile else 'desktop'
    browser.call('Emulation.setDeviceMetricsOverride', {'width': width, 'height': height, 'deviceScaleFactor': 1, 'mobile': mobile})
    browser.navigate('/')
    browser.wait("!!document.querySelector('#featured-appointment-title')")
    browser.evaluate("import('app').then(() => new Promise(resolve => setTimeout(resolve, 200)))")
    assert browser.evaluate('document.documentElement.scrollWidth') <= width, 'Horizontal overflow'
    assert browser.evaluate("document.querySelector('#appointment .btn').getAttribute('href')") == '/rendez-vous/types/1'
    assert browser.evaluate("document.querySelector('#appointment .small').getAttribute('href')") == '/rendez-vous/types'
    for theme in ['light', 'dark']:
        if browser.evaluate("document.documentElement.getAttribute('data-bs-theme')") != theme:
            browser.evaluate("document.querySelector('[data-action*=\"theme#toggle\"]').click()")
        browser.wait(f"document.documentElement.getAttribute('data-bs-theme') === '{theme}'")
        capture(f'home-{label}-{theme}.png')
    # Navigate the new links with real Tab / Enter events, in DOM order.
    browser.navigate('/')
    browser.wait("!!document.querySelector('#featured-appointment-title')")
    for attempt in range(50):
        key('Tab', 9)
        if browser.evaluate("document.activeElement.matches('#appointment a.btn')"):
            break
    else:
        raise AssertionError('Primary link unreachable with Tab')
    # Bootstrap animates the focus ring: wait for its final computed style.
    browser.evaluate('new Promise(resolve => setTimeout(resolve, 250))')
    focus = browser.evaluate("""(() => {const el = document.activeElement, s = getComputedStyle(el);
        return {visible:el.matches(':focus-visible'), shadow:s.boxShadow, outline:s.outlineStyle};})()""")
    visible_shadow = focus['shadow'] != 'none' and 'rgba(0, 0, 0, 0)' not in focus['shadow']
    assert focus['visible'] and (visible_shadow or focus['outline'] != 'none'), focus
    key('Tab', 9)
    assert browser.evaluate("document.activeElement.matches('#appointment a.small')"), 'Secondary link order'
    key('Enter', 13)
    browser.wait("location.pathname === '/rendez-vous/types'")
    browser.navigate('/')
    browser.wait("!!document.querySelector('#appointment a.btn')")
    browser.evaluate("document.querySelector('#appointment a.btn').focus()")
    key('Enter', 13)
    browser.wait("location.pathname === '/rendez-vous/types/1' && !!document.querySelector('.skhvxm')")
    checks.append({'viewport': label, 'themes': ['light', 'dark'], 'overflow': False, 'keyboard': 'passed', 'public_detail': 'passed', 'focus': focus})

assert browser.blocked_writes == 0, 'An unexpected write was attempted and blocked'
report = {'data': 'synthetic, SQLite in memory', 'checks': checks, 'write_requests': browser.blocked_writes,
          'external_requests_blocked': browser.blocked_external, 'reservation': 'never entered'}
(artifacts / 'results.json').write_text(json.dumps(report, indent=2), encoding='utf-8')
print(json.dumps(report))
