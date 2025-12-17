/// Sub-model of [UserModel]
class AuthorInfo {
  final String? fb, twitter, bio, website;

  AuthorInfo({this.fb, this.twitter, this.bio, this.website});

  factory AuthorInfo.fromMap(Map<String, dynamic> d) {
    return AuthorInfo(
        fb: d['fb'],
        website: d['website'],
        bio: d['bio'],
        twitter: d['twitter']);
  }

  static Map<String, dynamic> getMap(AuthorInfo d) {
    return {
      'fb': d.fb,
      'twitter': d.twitter,
      'bio': d.bio,
      'website': d.website,
    };
  }
}