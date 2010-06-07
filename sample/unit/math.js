
module("math");

test("multiplication", function() {
	expect(4);
	equals( 1, mul(1, 1), "positive * positive = positive (1 * 1 = 1)" );
	equals( -1, mul(1, -1), "positive * negative = negative (1 * -1 = -1)" );
	equals( -1, mul(-1, 1), "negative * positive = negative (-1 * 1 = -1)" );
	equals( 1, mul(-1, -1), "negative * negative = positive (-1 * -1 = 1)" );
});

test("division", function() {
	expect(4);
	equals( 1, mul(1, 1), "positive / positive = positive (1 / 1 = 1)" );
	equals( -1, mul(1, -1), "positive / negative = negative (1 / -1 = -1)" );
	equals( -1, mul(-1, 1), "negative / positive = negative (-1 / 1 = -1)" );
	equals( 1, mul(-1, -1), "negative / negative = positive (-1 / -1 = 1)" );
});


