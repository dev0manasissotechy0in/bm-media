import 'package:news_app/services/firestore_stub.dart';

class Subscription {
  final String plan, productId;
  DateTime purchaseAt, expireAt;

  Subscription({
    required this.plan,
    required this.purchaseAt,
    required this.expireAt,
    required this.productId,
  });

  factory Subscription.fromFirestore(Map<String, dynamic> d) {
    return Subscription(
        plan: d['plan'],
        purchaseAt: (d['purchased_at'] as Timestamp).toDate(),
        expireAt: (d['end_at'] as Timestamp).toDate(),
        productId: d['product_id']);
  }

  factory Subscription.fromJson(Map<String, dynamic> d) {
    return Subscription(
        plan: d['plan'],
        purchaseAt: DateTime.parse(d['purchased_at']),
        expireAt: DateTime.parse(d['end_at']),
        productId: d['product_id']);
  }

  static Map<String, dynamic> getMap(Subscription d) {
    return {
      'plan': d.plan,
      'purchased_at': d.purchaseAt,
      'end_at': d.expireAt,
      'product_id': d.productId,
    };
  }
}
