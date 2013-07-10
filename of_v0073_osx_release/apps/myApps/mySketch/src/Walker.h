//
//  Walker.h
//  emptyExample
//
//  Created by Tobias Treppmann on 5/17/13.
//
//

#ifndef __emptyExample__Walker__
#define __emptyExample__Walker__

#include <iostream>
#include "ofMain.h"

class Walker {
    public:
        void init();
        void step();
    
    protected:
    
        float x,y, stepsizex, stepsizey, tx,ty;
    
    private:
};



#endif /* defined(__emptyExample__Walker__) */
