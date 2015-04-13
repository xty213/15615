var expect = chai.expect;

describe("Single User API tests", function() {

    resetDB();

    describe("user login/logout lifecycle", function() {
        registerUser("johndoe");
        login("johndoe");
        logout();
        login("johndoe");
    });

    describe("timeline", function() {
        it("is by default empty", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/timeline.php')
                .success(function(data) {
                    var response = JSON.parse(data);
                    expect(response).to.deep.equal({
                        'status': 1,
                        'posts': []
                    });
                    alldone();
                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });

        it("can be posted with articles", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/post.php?title=hello%20world&flit=my%20life%20is%20cool')
                .success(function(data) {
                    var response = JSON.parse(data);
                    expect(response).to.deep.equal({
                        'status': 1
                    });
                    alldone();
                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });

        it("then show the posted article", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/timeline.php')
                .success(function(data) {
                    var response = JSON.parse(data);
                    expect(response.status).to.equal(1);
                    expect(response.posts).to.have.length(1);
                    expect(response.posts[0].title).to.equal('hello world');
                    expect(response.posts[0].username).to.equal('johndoe');
                    expect(response.posts[0].content).to.equal('my life is cool');
                    alldone();
                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });

        it("should show another article after posted", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/post.php?title=goodbye%20world&flit=my%20life%20is%20uncool')
                .success(function(data) {
                    var response = JSON.parse(data);
                    expect(response).to.deep.equal({
                        'status': 1
                    });
                    alldone();
                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });

        it("then show both posted articles, sorted by time", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/timeline.php')
                .success(function(data) {
                    var response = JSON.parse(data);
                    expect(response.status).to.equal(1);
                    expect(response.posts).to.have.length(2);
                    
                    expect(response.posts[0].title).to.equal('goodbye world');
                    expect(response.posts[0].username).to.equal('johndoe');
                    expect(response.posts[0].content).to.equal('my life is uncool');
                    expect(response.posts[0].pID).to.equal('2');

                    expect(response.posts[1].title).to.equal('hello world');
                    expect(response.posts[1].username).to.equal('johndoe');
                    expect(response.posts[1].content).to.equal('my life is cool');
                    expect(response.posts[1].pID).to.equal('1');
                    
                    alldone();
                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });

        it("should also support deletion", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/delete_post.php?pID=2')
                .success(function(data) {
                    expect(JSON.parse(data)).to.deep.equal({
                        'status': 1
                    });
                    $.ajax('../api/timeline.php')
                        .success(function(data) {
                            var response = JSON.parse(data);
                            expect(response.status).to.equal(1);
                            expect(response.posts).to.have.length(1);
                            expect(response.posts[0].title).to.equal('hello world');
                            expect(response.posts[0].username).to.equal('johndoe');
                            expect(response.posts[0].content).to.equal('my life is cool');
                            alldone();
                        })
                        .error(function(error) {
                            expect(error).to.be.undefined.
                            alldone();
                        });

                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });
    });

    describe('search posts', function() {
        it("returns article based on content", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/search.php?keyword=cool')
                .success(function(data) {
                    var response =
                        JSON.parse(data);
                    expect(response.status).to.equal(1);
                    expect(response.posts).to.have.length(1);
                    expect(response.posts[0].title).to.equal('hello world');
                    expect(response.posts[0].username).to.equal('johndoe');
                    expect(response.posts[0].content).to.equal('my life is cool');
                    alldone();
                }).error(function(error) {
                    expect(error).to.be.undefined.alldone();
                });

        });
    });

    describe('search users', function() {
        it("returns users based on document", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/user_search.php?username=doe')
                .success(function(data) {
                    var response =
                        JSON.parse(data);
                    expect(response.status).to.equal(1);
                    expect(response.users).to.have.length(1);
                    expect(response.users[0]).to.equal('johndoe');
                    alldone();
                }).error(function(error) {
                    expect(error).to.be.undefined.alldone();
                });

        });
    });

});

describe("Multiple User API tests", function() {
    resetDB();

    describe("register all the five users", function() {
        registerUser("user1");
        registerUser("user2");
        registerUser("user3");
        registerUser("user4");
        registerUser("user5");
    });

    userSendPost("user2", "post1");
    userSendPost("user3", "post2");
    userSendPost("user1", "post3");
    userSendPost("user5", "post4");
    userSendPost("user4", "post5");

    userLikesPost("user1", 1);
    userLikesPost("user1", 2);
    userLikesPost("user2", 2);
    userLikesPost("user2", 3);
    userLikesPost("user3", 1);
    userLikesPost("user4", 3);
    userLikesPost("user5", 1);
    userLikesPost("user5", 3);
    userLikesPost("user5", 5);

    describe("recommend for user1", function() {
        login("user1");
        it("should recommend post3 and post5", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/get_recommended_posts.php')
                .success(function(data) {
                    var response = JSON.parse(data);
                    expect(response.status).to.equal(1);
                    expect(response.posts).to.have.length(2);
                    expect(response.posts[0].title).to.equal('post3');
                    expect(response.posts[1].title).to.equal('post5');
                    alldone();
                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });
        logout();
    });
});

describe("Statistics", function() {
    it("user5 liked 3 posts", function(alldone) {
        this.timeout(5000);
        $.ajax('../api/get_num_likes_of_user.php?uID=user5')
            .success(function(data) {
                var response = JSON.parse(data);
                expect(response.status).to.equal(1);
                expect(response.count).to.equal('3');
                alldone();
            })
            .error(function(error) {
                expect(error).to.be.undefined.
                alldone();
            });
    });

    it("user1 posted 1 post", function(alldone) {
        this.timeout(5000);
        $.ajax('../api/get_num_posts.php?uID=user1')
            .success(function(data) {
                var response = JSON.parse(data);
                expect(response.status).to.equal(1);
                expect(response.count).to.equal('1');
                alldone();
            })
            .error(function(error) {
                expect(error).to.be.undefined.
                alldone();
            });
    });

    it("top 3 popular posts are post3, post1 and post2", function(alldone) {
        this.timeout(5000);
        $.ajax('../api/most_popular_posts.php?from=0&count=3')
            .success(function(data) {
                var response = JSON.parse(data);
                expect(response.status).to.equal(1);
                expect(response.posts).to.have.length(3);
                expect(response.posts[0].title).to.equal('post3');
                expect(response.posts[1].title).to.equal('post1');
                expect(response.posts[2].title).to.equal('post2');
                alldone();
            })
            .error(function(error) {
                expect(error).to.be.undefined.
                alldone();
            });
    });

    it("top 3 active users are user1, user2, user3", function(alldone) {
        this.timeout(5000);
        $.ajax('../api/most_active_users.php?count=3')
            .success(function(data) {
                var response = JSON.parse(data);
                expect(response.status).to.equal(1);
                expect(response.users).to.have.length(3);
                expect(response.users[0]).to.equal('user1');
                expect(response.users[1]).to.equal('user2');
                expect(response.users[2]).to.equal('user3');
                alldone();
            })
            .error(function(error) {
                expect(error).to.be.undefined.
                alldone();
            });
    });
});


describe("Corner cases", function() {
    it("user6 does not exist", function(alldone) {
        this.timeout(5000);
        $.ajax('../api/get_num_posts.php?uID=user6')
            .success(function(data) {
                var response = JSON.parse(data);
                expect(response.status).to.equal(0);
                alldone();
            })
            .error(function(error) {
                expect(error).to.be.undefined.
                alldone();
            });
    });

    describe("post title is too long", function() {
        login("user1");

        it("should fail", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/post.php?title=shouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfailshouldfail&flit=should%20fail')
                .success(function(data) {
                    var response = JSON.parse(data);
                    expect(response).to.deep.equal({
                        'status': 0
                    });
                    alldone();
                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });

        logout();
    });

    describe("unauthorized user try to send a post", function() {
        it("should fail", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/post.php?title=test&flit=should%20fail')
                .success(function(data) {
                    var response = JSON.parse(data);
                    expect(response).to.deep.equal({
                        'status': -1
                    });
                    alldone();
                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });
    });

    describe("try to like a post that does not exist", function() {
        login("user1");

        it("should fail", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/like.php?pID=1024')
                .success(function(data) {
                    var response = JSON.parse(data);
                    expect(response).to.deep.equal({
                        'status': 0
                    });
                    alldone();
                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });

        logout();
    });
});


function resetDB() {
    it("reset database", function(alldone) {
        this.timeout(5000);
        $.ajax("../api/reset.php?secret=15415Reset")
            .success(function(data) {
                var response = JSON.parse(data);
                expect(response).to.deep.equal({
                    'status': 1
                });
                alldone();
            })
            .error(function(error) {
                expect(error).to.be.undefined.
                alldone();
            });
    });
}

function registerUser(user) {
    it("register " + user, function(alldone) {
        this.timeout(5000);
        $.ajax('../api/register.php?username=' + user + '&pw=123456')
            .success(function(data) {
                var response = JSON.parse(data);
                expect(response).to.deep.equal({
                    'status': 1,
                    'userID': user
                });
                alldone();
            })
            .error(function(error) {
                expect(error).to.be.undefined.
                alldone();
            });
    });
}

function login(user) {
    it("login", function(alldone) {
        this.timeout(5000);
        $.ajax('../api/login.php?username=' + user + '&pw=123456')
            .success(function(data) {
                var response = JSON.parse(data);
                expect(response).to.deep.equal({
                    'status': 1,
                    'userID': user
                });
                alldone();
            })
            .error(function(error) {
                expect(error).to.be.undefined.
                alldone();
            });
    });
}

function logout() {
    it("logout", function(alldone) {
        this.timeout(5000);
        $.ajax('../api/logout.php')
            .success(function(data) {
                var response = JSON.parse(data);
                expect(response).to.deep.equal({
                    'status': 1
                });
                alldone();
            })
            .error(function(error) {
                expect(error).to.be.undefined.
                alldone();
            });
    });
}

function userSendPost(user, post) {
    describe(user + " post " + post, function() {
        login(user);

        it("send new post", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/post.php?title=' + post +'&flit=' + post)
                .success(function(data) {
                    var response = JSON.parse(data);
                    expect(response).to.deep.equal({
                        'status': 1
                    });
                    alldone();
                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });

        logout();
    });
}

function userLikesPost(user, postid) {
    describe(user + " likes post" + postid, function() {
        login(user);

        it("like that post", function(alldone) {
            this.timeout(5000);
            $.ajax('../api/like.php?pID=' + postid)
                .success(function(data) {
                    var response = JSON.parse(data);
                    expect(response).to.deep.equal({
                        'status': 1
                    });
                    alldone();
                })
                .error(function(error) {
                    expect(error).to.be.undefined.
                    alldone();
                });
        });

        logout();
    });
}
