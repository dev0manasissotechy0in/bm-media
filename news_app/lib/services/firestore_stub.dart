// Stub classes to allow compilation during migration from Firestore to REST API
// These will be removed once all screens are migrated

class DocumentSnapshot {
  final String id;
  final Map<String, dynamic>? _data;
  
  DocumentSnapshot(this.id, this._data);
  
  Map<String, dynamic>? data() => _data;
}

class QuerySnapshot {
  final List<DocumentSnapshot> docs;
  
  QuerySnapshot(this.docs);
}

class Timestamp {
  final DateTime _dateTime;
  
  Timestamp(this._dateTime);
  
  DateTime toDate() => _dateTime;
  
  static Timestamp now() => Timestamp(DateTime.now());
  static Timestamp fromDate(DateTime date) => Timestamp(date);
}
