#include <stdio.h>
#include <string.h>
#include "def.h"

// external functions
extern int check_word(char *word);
extern int strtolow(char *s);
extern int FindInsertionPosition(struct KeyRecord * KeyListTraverser, char *Key, int *Found, NUMKEYS NumKeys, int Count);
extern struct PageHdr *FetchPage(PAGENO Page);
extern PAGENO FindPageNumOfChild(struct PageHdr *PagePtr, struct KeyRecord *KeyListTraverser, char *Key, NUMKEYS NumKeys);

int get_predecessors(char *key, int k, char *result[]) {
    void find_predecessors(PAGENO PageNo, char *key, int k, char *result[], int *cnt);
    
    if (check_word(key) == FALSE) {
        return 0;
    }
    strtolow(key);

    int cnt = 0;
    find_predecessors(ROOT, key, k, result, &cnt);

    printf("found %d predecessors:\n", cnt);
    return cnt;
}

void find_predecessors(PAGENO PageNo, char *key, int k, char *result[], int *cnt) {
    struct PageHdr *PagePtr = FetchPage(PageNo);
    int pos, i, Found;
    // if is leaf, try to add records in this leaf node
    if (IsLeaf(PagePtr)) {
        char* childrenArr[PagePtr->NumKeys];
        struct KeyRecord *keyRecord = PagePtr->KeyListPtr;
        for (i = 0; i < PagePtr->NumKeys; i++) {
            childrenArr[i] = keyRecord->StoredKey;
            keyRecord = keyRecord->Next;
        }

        pos = FindInsertionPosition(PagePtr->KeyListPtr, key, &Found, PagePtr->NumKeys, 0);
        for (i = pos - 1; i >= 0 && k > *cnt; i--) {
            if (strcmp(childrenArr[i], key) == 0)
                continue;
            result[*cnt] = childrenArr[i];
            *cnt = *cnt + 1;
        }
    }
    // non-leaf: may find some predecessors here
    else if ((IsNonLeaf(PagePtr)) && (PagePtr->NumKeys > 0)) {
        // find the right page number
        PAGENO rightPage = FindPageNumOfChild(PagePtr, PagePtr->KeyListPtr, key, PagePtr->NumKeys);

        PAGENO childrenArr[PagePtr->NumKeys + 1];
        struct KeyRecord *keyRecord = PagePtr->KeyListPtr;
        for (i = 0; i < PagePtr->NumKeys; i++) {
            childrenArr[i] = keyRecord->PgNum;
            keyRecord = keyRecord->Next;
        }
        childrenArr[i] = PagePtr->PtrToFinalRtgPg;

        for (i = 0; i < PagePtr->NumKeys + 1; i++) {
            if (childrenArr[i] == rightPage) {
                pos = i;
                break;
            }
        }

        // try to find some predecessors
        for (i = pos; i >= 0 && *cnt < k; i--) {
            find_predecessors(childrenArr[i], key, k, result, cnt);
        }
    }
    return;
}

