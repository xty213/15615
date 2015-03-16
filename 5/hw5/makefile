ANSWERS = $(wildcard *.sql)
QUESTIONS = $(patsubst %.sql,%,$(ANSWERS))


all: $(QUESTIONS)

%: %.sql
	@echo "$@"
	psql $(USER) -q -c "\\i $<"
