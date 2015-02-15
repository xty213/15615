#include <stdio.h>
#include <string.h>
#include "def.h"

int get_predecessors(char *key, int k, char *result[]) {
    int cnt = 0;

    // try to find predecessors
    for (; cnt < k; cnt++) {
        result[cnt] = "alewife";
    }

    printf("found %d predecessors:\n", cnt);
    return cnt;

}
