parameters = JSON.parse(value);

var	opts = Browser.chromeOptions();
var	optsFirefox = Browser.firefoxOptions();
var	optsSafari = Browser.safariOptions();
var	optsEdge = Browser.edgeOptions()

Zabbix.log(5, JSON.stringify(optsFirefox))
Zabbix.log(5, JSON.stringify(optsSafari))
Zabbix.log(5, JSON.stringify(optsEdge))

function clickElement(browser, strategy, selector) {

	var el;

	try {
		el = browser.findElement(strategy, selector);
	}
	catch (error) {
		throw Error("cannot findElement "+ strategy + " " + selector);
	}

	if (el === null)
	{
		throw Error("cannot find "+ strategy + " " + selector);
	}

	try {
		el.click();
	}
	catch (error) {
		throw Error("cannot click "+ strategy + " " + selector);
	}
}

// uncomment for foreground
// opts.capabilities.alwaysMatch['goog:chromeOptions'].args = []

var browser = new Browser(opts)

try
{
	browser.setScreenSize(800, 600);
	browser.navigate(parameters.url);

	Zabbix.log(5, "getUrl: '"+ browser.getUrl()+"'")

	browser.setScriptTimeout(5000);
	browser.setSessionTimeout(1000);

	try
	{
		var elEarly = browser.findElements("text", "Web");
	}
	catch (error)
	{
		Zabbix.log(5, "invalid syntax findElements error handled: " + error);
	}

	if (null === browser.getError())
	{
		throw Error("invalid syntax findElements error not handled");
	}

	try
	{
		var elEarly = browser.findElement("xpath", "//input[@@id='name']");
	}
	catch (error)
	{
		Zabbix.log(5, "invalid syntax error handled: " + error);
	}

	if (null === browser.getError())
	{
		throw Error("invalid syntax findElement error not handled");
	}

	browser.setError("err.message");

	try {
		var elEarly = browser.findElement();
	}
	catch (error) {
		Zabbix.log(5, "invalid arguments error handled: " + error);
	}
	try {
		var elEarly = browser.findElement(null,null);
	}
	catch (error) {
		Zabbix.log(5, "invalid null arguments error handled: " + error);
		browser.discardError();
	}

	var el = browser.findElement("xpath", "//input[@id='foobar']")

	if (el === null)
	{
		Zabbix.log(5, "missing element handled");
	}

	var el = browser.findElements("xpath", "//input[@id='foobar']")

	if (el === null)
	{
		Zabbix.log(5, "missing elements handled");
	}

	source = browser.getPageSource()
	Zabbix.log(5, "source: " + source);

	browser.setElementWaitTimeout(5000);

	el = browser.findElement("xpath", "//input[@id='name']");
	el.sendKeys("A");
	el.sendKeys("d");
	try
	{
		Zabbix.log(5, "foo:" + el.getAttribute("foo"));
	}
	catch (error) {
		Zabbix.log(5, "cannot get attribute handled: " + error);
	}

	el = browser.findElement("xpath", "//input[@id='password']");
	if (el === null)
	{
		throw Error("cannot find password input field");
	}
	el.sendKeys("foo");
	el.clear();
	el.sendKeys("zabbi");

	el = browser.findElements("xpath", "//input");
	var i = 0;

	for (i = 0; i < el.length; i++)
	{
		if ("name" === el[i].getAttribute("id"))
		{
			el[i].sendKeys("min");
		}
		else if ("password" === el[i].getAttribute("id"))
		{
			el[i].sendKeys("x");
		}
	}

	el = browser.findElement("xpath", "//button[@id='enter']");

	if (el === null)
	{
		throw Error("cannot find login button");
	}

	Zabbix.log(5, "getText " + el.getText())

	el.click();

	clickElement(browser, "link text", "Data collection");

	el = browser.findElement("xpath", "//li[@id='config' and @class='has-submenu is-expanded']");
	if (el === null)
	{
		throw Error("cannot find //li[@id='config' and @class='has-submenu is-expanded']");
	}

	clickElement(browser, "link text", "Hosts");
	clickElement(browser, "xpath", "//input[@id='all_hosts']");
	clickElement(browser, "xpath", "//button[text()='Disable']");

	alert_window = browser.getAlert();

	alert_window.dismiss();

	el = browser.findElements("link text", "Web");
	Zabbix.log(5, "Web length: " + el.length)

	clickElement(browser, "link text", "Alerts");

	el = browser.findElement("xpath", "//li[@id='alerts' and contains(@class,'is-expanded')]");
	if (el === null)
	{
		throw Error("cannot find //li[@id='alerts' and contains(@class,'is-expanded')]");
	}

	Zabbix.sleep(250); // Alerts is clicked and Media Types slide up

	clickElement(browser, "link text", "Media types");
	clickElement(browser, "xpath", "//input[@id='all_media_types']");
	clickElement(browser,"xpath", "//button[text()='Enable']");

	alert_window = browser.getAlert();

	alert_window.accept();

	el = browser.findElement("xpath", "//a[text()='Enabled']");
	if (null === el)
	{
		throw Error("couldn't enable Media types");
	}

	cookies = browser.getCookies()

	for (i = 0; i < cookies.length; i++)
	{
		Zabbix.log(5, "cookie name: " + cookies[i].name + " value: " + cookies[i].value);
		if ("zbx_session" === cookies[i].name)
			break;
	}

	browser.collectPerfEntries();

	// start second browser, reuse zbx_session cookie and sign out
	var browser2 = new Browser(opts)

	try
	{
		Zabbix.log(5, "cookie: " + JSON.stringify(cookies[i]));
		browser2.navigate(parameters.url);
		browser2.setScreenSize(800, 600);
		browser2.addCookie(cookies[i]);

		browser2.navigate(parameters.url);

		browser2.setElementWaitTimeout(3000);
		el = browser2.findElement("xpath", "//div[@class='dashboard is-ready']");
		if (el === null)
		{
			throw Error("cannot find dashboard is-ready");
		}

		browser2.setElementWaitTimeout(0);

		el = browser2.findElement("xpath", "//div[@class='no-data-message']");
		if (el === null)
		{
			throw Error("cannot find no data message");
		}

		browser2.setElementWaitTimeout(3000);

		clickElement(browser2, "link text", "Dashboards");
		clickElement(browser2, "xpath", "//button[contains(.,'Edit dashboard')]");
		clickElement(browser2, "xpath", "//button[contains(.,'Add')]");
		clickElement(browser2, "xpath", "//button[@id='label-type']");
		clickElement(browser2, "xpath", "//li[@value='url']");
		el = browser2.findElement("xpath", "//input[@id='url']");
		if (el === null) {
			throw Error("cannot find url");
		}
		el.sendKeys(parameters.url + "zabbix.php?action=queue.overview");

		clickElement(browser2, "xpath", "//button[@class='dialogue-widget-save']");

		el = browser2.findElement("xpath", "//iframe");
		if (el === null) {
			throw Error("cannot get iframe");
		}

		browser2.switchFrame(el);

		el = browser2.findElement("xpath", "//h1[contains(.,'Queue overview')]");
		if (el === null) {
			throw Error("cannot find Queue overview");
		}

		browser2.switchFrame();
		browser2.switchFrame(0);

		el = browser2.findElement("xpath", "//h1[contains(.,'Queue overview')]");
		if (el === null) {
			throw Error("cannot find Queue overview");
		}

		browser2.switchFrame();

		clickElement(browser2, "xpath", "//a[contains(.,'Cancel')]");

		Zabbix.log(5, "screenshot: " + browser2.getScreenshot());

		el = browser2.findElement("link text", "Sign out");

		browser2.collectPerfEntries();

		try
		{
			raw = browser2.getRawPerfEntries()
		}
		catch (error) {
			Zabbix.log(5, "cannot get getRawPerfEntries: " + error);
		}

		try
		{
			raw = browser2.getRawPerfEntriesByType('\'\+\'navigation');
		}
		catch (error)
		{
			Zabbix.log(5, "cannot get getRawPerfEntriesByType: " + error);
		}

		if (null === browser2.getError())
		{
			throw Error("injection not handled");
		}

		var raw = browser2.getRawPerfEntriesByType('navigation');
		browserDashboardResult = browser2.getResult();
		browserDashboardResult.raw = raw.concat(browser2.getRawPerfEntriesByType('resource'));

		summary = browserDashboardResult.performance_data.summary;

		if (summary.resource.response_time > browserDashboardResult.duration ||
			summary.resource.tcp_handshake_time > browserDashboardResult.duration ||
			summary.resource.service_worker_processing_time > browserDashboardResult.duration ||
			summary.resource.tls_negotiation_time > browserDashboardResult.duration ||
			summary.resource.resource_fetch_time > browserDashboardResult.duration ||
			summary.resource.request_time > browserDashboardResult.duration)
		{
			Zabbix.log(5, "Duration of test: " +browserDashboardResult.duration+" is less than response time: " + JSON.stringify(summary.resource));
		}
	}
	catch (error)
	{
		throw error;
	}

	if (el === null)
	{
		throw Error("cannot find logout button");
	}
	el.click();

	browser2.navigate(parameters.url);
	elSignOut = browser2.findElement("link text", "Sign out");

	if (elSignOut != null)
	{
		throw Error("logged in without password after sign out");
	}

	var bypass = {};

	bypass[atob('//9k')] = 'test';
	bypass.navigate = browser.navigate;

	try
	{
		bypass.navigate('test');
	}
	catch (error)
	{
		Zabbix.log(5, "navigation bypass handled " + error);
	}

	var bypass_alert = {};

	bypass_alert.dismiss = alert_window.dismiss

	try
	{
		bypass_alert.dismiss();
	}
	catch (error)
	{
		Zabbix.log(5, "alert bypass handled " + error);
	}

	var bypass_el = {};

	bypass_el.click = el.click;
	try
	{
		bypass_el.click();
	}
	catch (error)
	{
		Zabbix.log(5, "alert click handled " + error);
	}
	var elTest = new Object();

	try
	{
		browser2.switchFrame(elTest);
	}
	catch (error)
	{
		Zabbix.log(5, "invalid parameter handled " + error);
	}
	try
	{
		browser2.switchFrame(0);
	}
	catch (error)
	{
		Zabbix.log(5, "missing frame error handled " + error);
	}
}
catch (err)
{
	if (!(err instanceof BrowserError))
	{
		browser.setError(err.message);
	}

	if (null === browser.getError())
	{
		browser.setError(err.message);
	}

	result = browser.getResult();
	var screenshot = browser.getScreenshot();
	return JSON.stringify(result);

}

result = browser.getResult();
result.browserDashboard = browserDashboardResult;
return JSON.stringify(result);
